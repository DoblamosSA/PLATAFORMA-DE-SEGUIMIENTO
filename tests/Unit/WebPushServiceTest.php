<?php

namespace Tests\Unit;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class WebPushServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_colaborador_solo_recibe_push_de_su_proyecto(): void
    {
        $depto = Department::factory()->create();
        $subA = SubDepartment::factory()->create(['department_id' => $depto->id]);
        $subB = SubDepartment::factory()->create(['department_id' => $depto->id]);

        $edison = User::factory()->create(['rol' => 'tecnico', 'name' => 'Edison']);
        $darwin = User::factory()->create(['rol' => 'tecnico', 'name' => 'Darwin']);
        $admin = User::factory()->create(['rol' => 'admin', 'name' => 'Admin']);

        $depto->users()->attach($edison->id, ['es_principal' => true]);
        $depto->users()->attach($darwin->id, ['es_principal' => true]);
        $depto->users()->attach($admin->id, ['es_principal' => true]);

        $cotizador = Project::create([
            'nombre' => 'COTIZADOR',
            'sub_department_id' => $subA->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
            'responsable_id' => $edison->id,
        ]);
        $cotizador->equipo()->attach($edison->id, ['rol_en_proyecto' => 'desarrollador']);

        $gtd = Project::create([
            'nombre' => 'GTD',
            'sub_department_id' => $subB->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
            'responsable_id' => $darwin->id,
        ]);
        $gtd->equipo()->attach($darwin->id, ['rol_en_proyecto' => 'desarrollador']);

        $tareaGtd = Task::create([
            'project_id' => $gtd->id,
            'titulo' => 'Tarea GTD',
            'sub_department_id' => $subB->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $darwin->id,
            'fecha_asignacion' => now(),
            'fecha_limite' => now()->addDays(2),
        ]);

        $svc = app(WebPushService::class);
        $destinatarios = $svc->idsDestinatarios($gtd, $tareaGtd);

        $this->assertContains($darwin->id, $destinatarios);
        $this->assertContains($admin->id, $destinatarios);
        $this->assertNotContains($edison->id, $destinatarios);
    }

    public function test_admin_del_departamento_recibe_push_de_ambos_proyectos(): void
    {
        $depto = Department::factory()->create();
        $otroDepto = Department::factory()->create();
        $sub = SubDepartment::factory()->create(['department_id' => $depto->id]);
        $subAjeno = SubDepartment::factory()->create(['department_id' => $otroDepto->id]);

        $admin = User::factory()->create(['rol' => 'admin']);
        $adminAjeno = User::factory()->create(['rol' => 'admin']);
        $colab = User::factory()->create(['rol' => 'tecnico']);

        $depto->users()->attach($admin->id, ['es_principal' => true]);
        $depto->users()->attach($colab->id, ['es_principal' => true]);
        $otroDepto->users()->attach($adminAjeno->id, ['es_principal' => true]);

        $proyecto = Project::create([
            'nombre' => 'COTIZADOR',
            'sub_department_id' => $sub->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
            'responsable_id' => $colab->id,
        ]);
        $proyecto->equipo()->attach($colab->id, ['rol_en_proyecto' => 'desarrollador']);

        $proyectoAjeno = Project::create([
            'nombre' => 'OTRO',
            'sub_department_id' => $subAjeno->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
            'responsable_id' => $adminAjeno->id,
        ]);

        $svc = app(WebPushService::class);

        $deEsteDepto = $svc->idsDestinatarios($proyecto);
        $this->assertContains($admin->id, $deEsteDepto);
        $this->assertContains($colab->id, $deEsteDepto);
        $this->assertNotContains($adminAjeno->id, $deEsteDepto);

        $delOtro = $svc->idsDestinatarios($proyectoAjeno);
        $this->assertContains($adminAjeno->id, $delOtro);
        $this->assertNotContains($admin->id, $delOtro);
        $this->assertNotContains($colab->id, $delOtro);
    }
}
