<?php

namespace App\Services;

use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\RoleRepositoryInterface;
use App\Models\Project;
use App\Models\PushSubscription;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envia notificaciones Web Push (VAPID) a los dispositivos suscritos.
 * Las suscripciones caducadas (endpoint 404/410) se eliminan al vuelo.
 *
 * Alcance de destinatarios:
 * - Colaboradores: solo proyectos donde son responsable o integrantes del equipo
 *   (y, si aplica, asignado/creador de la tarea).
 * - Administradores del departamento: todos los proyectos/tareas de su departamento.
 * - Super Administrador (rol global): todo.
 */
class WebPushService
{
    /** URL del bundle Mozilla de CAs (usado si PHP no tiene curl.cainfo). */
    private const CA_BUNDLE_URL = 'https://curl.se/ca/cacert.pem';

    /**
     * Notifica un cambio a los usuarios relevantes del proyecto/tarea,
     * excluyendo al autor del cambio.
     *
     * El envio se difiere hasta despues de la respuesta HTTP: en `php artisan
     * serve` (un solo hilo) un flush sincrono a FCM/WNS bloquea el servidor y
     * el service worker del escritorio no puede cargar el icono, asi que la
     * toast de Windows no aparece o llega muy tarde.
     */
    public function notificarATodos(
        ?int $exceptoUserId,
        string $titulo,
        string $cuerpo,
        string $url = '/',
        ?Project $proyecto = null,
        ?Task $tarea = null,
    ): void {
        app()->terminating(function () use ($exceptoUserId, $titulo, $cuerpo, $url, $proyecto, $tarea) {
            $this->enviarAhora($exceptoUserId, $titulo, $cuerpo, $url, $proyecto, $tarea);
        });
    }

    /**
     * Envio inmediato (tests, comandos artisan, jobs).
     */
    public function enviarAhora(
        ?int $exceptoUserId,
        string $titulo,
        string $cuerpo,
        string $url = '/',
        ?Project $proyecto = null,
        ?Task $tarea = null,
    ): void {
        $destinatarios = $this->idsDestinatarios($proyecto, $tarea);

        if ($destinatarios === []) {
            return;
        }

        $suscripciones = PushSubscription::query()
            ->whereIn('user_id', $destinatarios)
            ->when($exceptoUserId, fn ($q) => $q->where('user_id', '!=', $exceptoUserId))
            ->get();

        if ($suscripciones->isEmpty()) {
            return;
        }

        $this->enviar($suscripciones, $titulo, $cuerpo, $url);
    }

    /**
     * Usuarios que deben recibir push por un cambio en el proyecto/tarea.
     *
     * @return list<int>
     */
    public function idsDestinatarios(?Project $proyecto = null, ?Task $tarea = null): array
    {
        $ids = collect();

        // Super Administrador global (RBAC), independiente del departamento.
        $ids = $ids->merge(
            User::query()
                ->whereHas('rolesGlobales', fn ($q) => $q->where('slug', 'super-admin'))
                ->pluck('id')
        );

        if ($tarea) {
            if ($tarea->asignado_id) {
                $ids->push((int) $tarea->asignado_id);
            }
            if ($tarea->creado_por) {
                $ids->push((int) $tarea->creado_por);
            }
            $proyecto ??= $tarea->proyecto;
        }

        if ($proyecto) {
            if ($proyecto->responsable_id) {
                $ids->push((int) $proyecto->responsable_id);
            }

            $ids = $ids->merge($proyecto->equipo()->pluck('users.id'));

            $departamentoId = $proyecto->relationLoaded('subDepartamento')
                ? $proyecto->subDepartamento?->department_id
                : $proyecto->subDepartamento()->value('department_id');

            if ($departamentoId) {
                $ids = $ids->merge($this->idsAdminsDelDepartamento((int) $departamentoId));
            }
        } elseif ($tarea) {
            $departamentoId = $tarea->relationLoaded('subDepartamento')
                ? $tarea->subDepartamento?->department_id
                : $tarea->subDepartamento()->value('department_id');

            if ($departamentoId) {
                $ids = $ids->merge($this->idsAdminsDelDepartamento((int) $departamentoId));
            }
        }

        return $ids
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Administradores del departamento: users.rol = admin en ese depto,
     * o rol de departamento cuya raiz RBAC es admin/super-admin.
     *
     * @return Collection<int, int>
     */
    protected function idsAdminsDelDepartamento(int $departamentoId): Collection
    {
        $porRolLegado = User::query()
            ->where('rol', 'admin')
            ->whereHas('departments', fn ($q) => $q->where('departments.id', $departamentoId))
            ->pluck('id');

        $candidatos = User::query()
            ->whereHas('departments', function ($q) use ($departamentoId) {
                $q->where('departments.id', $departamentoId)
                    ->whereNotNull('department_user.role_id');
            })
            ->with(['departments' => fn ($q) => $q->where('departments.id', $departamentoId)])
            ->get();

        $roles = app(RoleRepositoryInterface::class);
        $porRolDepartamento = $candidatos
            ->filter(function (User $user) use ($departamentoId, $roles) {
                $roleId = $user->departments->firstWhere('id', $departamentoId)?->pivot?->role_id;
                if (! $roleId) {
                    return false;
                }

                $role = Role::find($roleId);
                if (! $role) {
                    return false;
                }

                $raiz = $roles->ancestorsOf($role)->first()?->slug ?? $role->slug;

                return in_array($raiz, ['admin', 'super-admin'], true);
            })
            ->pluck('id');

        return $porRolLegado->merge($porRolDepartamento)->map(fn ($id) => (int) $id)->unique();
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

        $ca = $this->rutaCertificadosCa();
        $this->aplicarCertificadosCa($ca);

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
                    'verify' => $ca,
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
            // Tag unico: en Windows, el mismo tag reemplaza la toast anterior
            // y a veces solo queda en el Centro de actividades sin banner.
            'tag' => 'projects-'.sha1($titulo.'|'.$cuerpo.'|'.$url.'|'.microtime(true)),
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
     * Propaga el CA bundle a variables/ini que cURL/OpenSSL consultan por
     * defecto, por si alguna capa ignora el `verify` de Guzzle.
     */
    protected function aplicarCertificadosCa(string|bool $ca): void
    {
        if (! is_string($ca) || $ca === '') {
            return;
        }

        if (! getenv('SSL_CERT_FILE')) {
            putenv('SSL_CERT_FILE='.$ca);
        }
        if (! getenv('CURL_CA_BUNDLE')) {
            putenv('CURL_CA_BUNDLE='.$ca);
        }
        if (! ini_get('curl.cainfo')) {
            @ini_set('curl.cainfo', $ca);
        }
        if (! ini_get('openssl.cafile')) {
            @ini_set('openssl.cafile', $ca);
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
            // Bundle versionado en el repo (preferido en Windows/dev).
            base_path('resources/certs/cacert.pem'),
            storage_path('app/certs/cacert.pem'),
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
