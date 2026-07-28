<?php

namespace Tests\Unit;

use App\Services\WebPushService;
use ReflectionMethod;
use Tests\TestCase;

class WebPushServiceTest extends TestCase
{
    public function test_resuelve_bundle_ca_versionado_en_resources(): void
    {
        $bundle = base_path('resources/certs/cacert.pem');
        $this->assertFileExists($bundle);
        $this->assertGreaterThan(1000, filesize($bundle));

        $svc = app(WebPushService::class);
        $metodo = new ReflectionMethod($svc, 'rutaCertificadosCa');
        $metodo->setAccessible(true);

        $ruta = $metodo->invoke($svc);

        $this->assertIsString($ruta);
        $this->assertFileIsReadable($ruta);
    }
}
