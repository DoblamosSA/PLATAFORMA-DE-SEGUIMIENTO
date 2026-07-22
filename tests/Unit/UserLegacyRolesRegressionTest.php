<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkpoint de "cero regresion": el trait HasOrganizationAccess y las
 * migraciones nuevas no deben alterar en absoluto el comportamiento de los
 * metodos existentes de User relacionados al sistema de roles legado.
 */
class UserLegacyRolesRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_metodos_es_rol_se_comportan_igual_que_antes(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $lider = User::factory()->create(['rol' => 'lider']);
        $tecnico = User::factory()->create(['rol' => 'tecnico']);
        $evaluador = User::factory()->create(['rol' => 'evaluador']);

        $this->assertTrue($admin->esAdmin());
        $this->assertFalse($lider->esAdmin());

        $this->assertTrue($admin->esLider());
        $this->assertTrue($lider->esLider());
        $this->assertFalse($tecnico->esLider());

        $this->assertTrue($admin->esCoordinador());
        $this->assertTrue($lider->esCoordinador());
        $this->assertFalse($evaluador->esCoordinador());

        $this->assertTrue($tecnico->esColaborador());
        $this->assertFalse($admin->esColaborador());

        $this->assertTrue($evaluador->esEvaluador());
        $this->assertFalse($tecnico->esEvaluador());
    }

    public function test_metodos_puede_x_se_comportan_igual_que_antes(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $lider = User::factory()->create(['rol' => 'lider']);
        $tecnico = User::factory()->create(['rol' => 'tecnico']);
        $evaluador = User::factory()->create(['rol' => 'evaluador']);

        $this->assertTrue($admin->puedeCrearTarea());
        $this->assertFalse($lider->puedeCrearTarea());

        $this->assertTrue($admin->puedeCrearProyecto());
        $this->assertTrue($lider->puedeCrearProyecto());
        $this->assertFalse($tecnico->puedeCrearProyecto());

        $this->assertTrue($admin->puedeCrearSubtarea());
        $this->assertTrue($lider->puedeCrearSubtarea());
        $this->assertTrue($tecnico->puedeCrearSubtarea());
        $this->assertTrue($evaluador->puedeCrearSubtarea());

        $this->assertTrue($admin->puedeEliminarSubtarea());
        $this->assertFalse($lider->puedeEliminarSubtarea());

        $this->assertTrue($admin->puedeRechazarTarea());
        $this->assertTrue($evaluador->puedeRechazarTarea());
        $this->assertFalse($tecnico->puedeRechazarTarea());
    }

    public function test_puede_eliminar_tarea_se_comporta_igual_que_antes(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $lider = User::factory()->create(['rol' => 'lider']);

        $project = Project::create([
            'nombre' => 'Proyecto regresion',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);

        $tareaSinSubtareas = Task::create([
            'project_id' => $project->id,
            'titulo' => 'Tarea sin subtareas',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
        ]);

        $this->assertTrue($admin->puedeEliminarTarea($tareaSinSubtareas));
        $this->assertTrue($lider->puedeEliminarTarea($tareaSinSubtareas));
    }
}
