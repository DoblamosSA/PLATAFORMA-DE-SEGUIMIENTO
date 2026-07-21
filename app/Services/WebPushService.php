<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envia notificaciones Web Push (VAPID) a los dispositivos suscritos.
 * Las suscripciones caducadas (endpoint 404/410) se eliminan al vuelo.
 */
class WebPushService
{
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
            return; // Sin claves VAPID configuradas no hay push.
        }

        // En Windows, la encriptacion del payload necesita ubicar openssl.cnf.
        if (! empty($config['openssl_conf']) && ! getenv('OPENSSL_CONF')) {
            putenv('OPENSSL_CONF='.$config['openssl_conf']);
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $config['subject'],
                    'publicKey' => $config['public_key'],
                    'privateKey' => $config['private_key'],
                ],
            ], [], 10); // timeout 10s para no colgar la peticion
        } catch (\Throwable $e) {
            Log::warning('WebPush: no se pudo inicializar el cliente: '.$e->getMessage());

            return;
        }

        $payload = json_encode([
            'title' => $titulo,
            'body' => $cuerpo,
            'url' => $url,
        ]);

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
}
