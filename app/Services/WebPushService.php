<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envia notificaciones Web Push (VAPID) a los dispositivos suscritos.
 * Las suscripciones caducadas (endpoint 404/410) se eliminan al vuelo.
 */
class WebPushService
{
    /** URL del bundle Mozilla de CAs (usado si PHP no tiene curl.cainfo). */
    private const CA_BUNDLE_URL = 'https://curl.se/ca/cacert.pem';

    /**
     * Notifica un cambio a todos los usuarios suscritos, excluyendo al autor
     * del cambio (no tiene sentido notificarse a si mismo).
     */
    public function notificarATodos(?int $exceptoUserId, string $titulo, string $cuerpo, string $url = '/'): void
    {
        $suscripciones = PushSubscription::query()
            ->when($exceptoUserId, fn ($q) => $q->where('user_id', '!=', $exceptoUserId))
            ->get();

        if ($suscripciones->isEmpty()) {
            return;
        }

        $this->enviar($suscripciones, $titulo, $cuerpo, $url);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PushSubscription>  $suscripciones
     */
    protected function enviar($suscripciones, string $titulo, string $cuerpo, string $url): void
    {
        $config = config('services.webpush');

        if (empty($config['public_key']) || empty($config['private_key'])) {
            Log::warning('WebPush: VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY no configuradas; no se envian notificaciones.');

            return;
        }

        // En Windows, la encriptacion del payload necesita ubicar openssl.cnf.
        if (! empty($config['openssl_conf']) && ! getenv('OPENSSL_CONF')) {
            putenv('OPENSSL_CONF='.$config['openssl_conf']);
        }

        try {
            $webPush = new WebPush(
                [
                    'VAPID' => [
                        'subject' => $config['subject'],
                        'publicKey' => $config['public_key'],
                        'privateKey' => $config['private_key'],
                    ],
                ],
                [],
                10, // timeout 10s para no colgar la peticion
                [
                    // Sin CA bundle, cURL en Windows falla con error 60 y el
                    // push nunca llega (ni a FCM ni a WNS).
                    'verify' => $this->rutaCertificadosCa(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('WebPush: no se pudo inicializar el cliente: '.$e->getMessage());

            return;
        }

        $payload = json_encode([
            'title' => $titulo,
            'body' => $cuerpo,
            'url' => $url,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($suscripciones as $s) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $s->endpoint,
                'keys' => ['p256dh' => $s->p256dh, 'auth' => $s->auth],
            ]), $payload);
        }

        try {
            foreach ($webPush->flush() as $reporte) {
                if ($reporte->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', hash('sha256', $reporte->getEndpoint()))->delete();
                } elseif (! $reporte->isSuccess()) {
                    Log::warning('WebPush: fallo el envio: '.$reporte->getReason());
                }
            }
        } catch (\Throwable $e) {
            // Un fallo de red al enviar push nunca debe romper la operacion
            // de negocio que lo origino.
            Log::warning('WebPush: error al enviar notificaciones: '.$e->getMessage());
        }
    }

    /**
     * Resuelve un archivo de CAs usable por Guzzle/cURL.
     * En muchos PHP de Windows curl.cainfo/openssl.cafile estan vacios y
     * cualquier POST a FCM/WNS revienta con cURL error 60.
     */
    protected function rutaCertificadosCa(): string|bool
    {
        $configurado = config('services.webpush.ca_file') ?: getenv('SSL_CERT_FILE') ?: null;
        if (is_string($configurado) && $configurado !== '' && is_readable($configurado)) {
            return $configurado;
        }

        foreach (['curl.cainfo', 'openssl.cafile'] as $ini) {
            $valor = ini_get($ini);
            if (is_string($valor) && $valor !== '' && is_readable($valor)) {
                return $valor;
            }
        }

        $candidatos = [
            storage_path('app/certs/cacert.pem'),
            base_path('resources/certs/cacert.pem'),
            'C:/PHP/extras/ssl/cacert.pem',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/cert.pem',
        ];

        foreach ($candidatos as $ruta) {
            if (is_readable($ruta)) {
                return $ruta;
            }
        }

        $descargado = $this->asegurarBundleCaLocal();
        if ($descargado) {
            return $descargado;
        }

        return true; // default del sistema (Linux/Docker suele funcionar)
    }

    /**
     * Descarga el bundle Mozilla a storage/app/certs si hace falta.
     * Fallar aqui no debe tumbar la app: solo deja el verify en default.
     */
    protected function asegurarBundleCaLocal(): ?string
    {
        $destino = storage_path('app/certs/cacert.pem');

        if (is_readable($destino) && filesize($destino) > 1000) {
            return $destino;
        }

        try {
            if (! is_dir(dirname($destino))) {
                mkdir(dirname($destino), 0755, true);
            }

            $respuesta = Http::timeout(20)->withOptions(['verify' => false])->get(self::CA_BUNDLE_URL);
            if (! $respuesta->successful() || strlen($respuesta->body()) < 1000) {
                Log::warning('WebPush: no se pudo descargar el bundle de CAs (HTTP '.$respuesta->status().').');

                return null;
            }

            file_put_contents($destino, $respuesta->body());

            return is_readable($destino) ? $destino : null;
        } catch (\Throwable $e) {
            Log::warning('WebPush: fallo al preparar cacert.pem: '.$e->getMessage());

            return null;
        }
    }
}
