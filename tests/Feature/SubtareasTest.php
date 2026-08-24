<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
use App\Livewire\Proyectos\TableroProyecto;
use App\Livewire\Tareas\ListaTareas;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubtareasTest extends TestCase
{
    use RefreshDatabase;

    private User $lider;

    private User $dev;

    private User $ajeno;

    private Project $project;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);

        $this->lider = User::factory()->create(['rol' => 'lider']);
        $this->dev = User::factory()->create(['rol' => 'tecnico']);
        $this->ajeno = User::factory()->create(['rol' => 'tecnico']);

        $department->users()->attach($this->lider->id, ['es_principal' => true]);
        $department->users()->attach($this->dev->id, ['es_principal' => true]);

        $otroDepto = Department::factory()->create();
        $otroDepto->users()->attach($this->ajeno->id, ['es_principal' => true]);

        $this->project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'sub_department_id' => $subDepartment->id,
            'estado' => 'en_progreso',
            'prioridad' => 'alta',
            'responsable_id' => $this->lider->id,
        ]);

        $this->project->equipo()->attach($this->dev->id, ['rol_en_proyecto' => 'desarrollador']);

        $this->otorgarPermiso($this->dev, 'subtasks.create');

        $this->task = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea con subtareas',
            'sub_department_id' => $subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
            'fecha_inicio' => now(),
            'fecha_limite' => now()->addDays(3),
        ]);
    }

    private function otorgarPermiso(User $user, string $slug): void
    {
        $permiso = Permission::factory()->create(['slug' => $slug]);
        $rol = Role::factory()->create(['is_primary' => true]);
        $rol->permissions()->attach($permiso->id, ['tipo' => 'grant']);
        $user->departments()->updateExistingPivot(
            $user->departments()->first()->id,
            ['role_id' => $rol->id]
        );
    }

    public function test_agregar_una_subtarea_actualiza_las_horas_estimadas_de_la_tarea_principal(): void
    {
        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id)
            ->set('nuevaSubtareaTitulo', 'Subtarea one')
            ->set('nuevaSubtareaHoras', '3')
            ->call('agregarSubtarea')
            ->assertHasNoErrors();

        $this->assertEquals(3, $this->task->fresh()->horas_estimadas);
    }

    public function test_varias_subtareas_se_suman_en_las_horas_de_la_tarea_principal(): void
    {
        $componente = Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id);

        $componente->set('nuevaSubtareaTitulo', 'Subtarea one')
            ->set('nuevaSubtareaHoras', '3')
            ->call('agregarSubtarea');

        $componente->set('nuevaSubtareaTitulo', 'Subtarea two')
            ->set('nuevaSubtareaHoras', '5')
            ->call('agregarSubtarea');

        $this->assertEquals(8, $this->task->fresh()->horas_estimadas);
        $this->assertCount(2, $this->task->fresh()->subtareas);
    }

    public function test_agregar_subtarea_limpia_el_formulario_y_registra_trazabilidad(): void
    {
        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id)
            ->set('nuevaSubtareaTitulo', 'Diseño de base de datos')
            ->set('nuevaSubtareaHoras', '4')
            ->call('agregarSubtarea')
            ->assertSet('nuevaSubtareaTitulo', '')
            ->assertSet('nuevaSubtareaHoras', null);

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $this->task->id,
            'accion' => 'subtarea',
        ]);

        $actividad = $this->task->actividades()->where('accion', 'subtarea')->first();
        $this->assertStringContainsString('Diseño de base de datos', $actividad->detalle);
        $this->assertStringContainsString('4h', $actividad->detalle);
    }

    public function test_el_titulo_de_la_subtarea_es_obligatorio(): void
    {
        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id)
            ->set('nuevaSubtareaTitulo', '')
            ->set('nuevaSubtareaHoras', '3')
            ->call('agregarSubtarea')
            ->assertHasErrors(['nuevaSubtareaTitulo' => 'required']);

        $this->assertNull($this->task->fresh()->horas_estimadas);
    }

    public function test_las_horas_deben_ser_numericas_y_mayores_a_cero(): void
    {
        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id)
            ->set('nuevaSubtareaTitulo', 'Subtarea invalida')
            ->set('nuevaSubtareaHoras', 'no-es-un-numero')
            ->call('agregarSubtarea')
            ->assertHasErrors(['nuevaSubtareaHoras' => 'numeric']);

        Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id)
            ->set('nuevaSubtareaTitulo', 'Subtarea invalida')
            ->set('nuevaSubtareaHoras', '0')
            ->call('agregarSubtarea')
            ->assertHasErrors(['nuevaSubtareaHoras' => 'min']);
    }

    public function test_un_usuario_ajeno_al_proyecto_no_puede_acceder_al_tablero_para_agregar_subtareas(): void
    {
        // La autorizacion se valida en mount(): un ajeno nunca llega a
        // instanciar el componente para poder llamar a agregarSubtarea().
        $this->actingAs($this->ajeno)
            ->get(route('proyectos.tablero', $this->project))
            ->assertForbidden();

        $this->assertDatabaseCount('subtasks', 0);
    }

    public function test_agregar_subtarea_que_excede_capacidad_bloquea_y_referencia_la_tarea_que_la_agota(): void
    {
        $this->dev->update(['dias_laborales' => ['L'], 'horas_diarias' => 8]);

        $tareaPrevia = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea previa que agota la semana',
            'sub_department_id' => $this->task->sub_department_id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'asignado_id' => $this->dev->id,
            'fecha_asignacion' => now(),
            'fecha_inicio' => now(),
            'fecha_limite' => now()->addDays(3),
            'horas_estimadas' => 8,
        ]);

        $this->task->update(['asignado_id' => $this->dev->id]);

        $componente = Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id)
            ->set('nuevaSubtareaTitulo', 'Subtarea que no cabe')
            ->set('nuevaSubtareaHoras', '2')
            ->call('agregarSubtarea');

        $componente->assertHasErrors(['nuevaSubtareaHoras']);
        $componente->assertSet('tareaBloqueanteCapacidad.id', $tareaPrevia->id);

        // El enlace debe llevar al tablero del proyecto de esa tarea (panel
        // lateral con subtareas), no al modal generico de "Editar tarea".
        $componente->assertSee(route('proyectos.tablero', ['project' => $this->project->id, 'tarea' => $tareaPrevia->id]), false);

        $this->assertCount(0, $this->task->fresh()->subtareas);

        $actividad = $this->task->actividades()->where('accion', 'bloqueo_capacidad')->first();
        $this->assertNotNull($actividad);
        $this->assertStringContainsString('Superas la capacidad de trabajo', $actividad->detalle);
        $this->assertStringContainsString('Tarea previa que agota la semana', $actividad->detalle);
    }

    public function test_las_subtareas_se_mantienen_en_orden_de_creacion(): void
    {
        $componente = Livewire::actingAs($this->dev)
            ->test(TableroProyecto::class, ['project' => $this->project])
            ->call('abrirTarea', $this->task->id);

        $componente->set('nuevaSubtareaTitulo', 'Primera')->set('nuevaSubtareaHoras', '1')->call('agregarSubtarea');
        $componente->set('nuevaSubtareaTitulo', 'Segunda')->set('nuevaSubtareaHoras', '2')->call('agregarSubtarea');
        $componente->set('nuevaSubtareaTitulo', 'Tercera')->set('nuevaSubtareaHoras', '3')->call('agregarSubtarea');

        $this->assertSame(
            ['Primera', 'Segunda', 'Tercera'],
            $this->task->fresh()->subtareas->pluck('titulo')->all()
        );
    }

    public function test_un_no_admin_con_permiso_puede_eliminar_una_tarea_que_tiene_subtareas(): void
    {
        // Tener subtareas ya no bloquea el borrado para no-admins (antes solo
        // el admin podia borrar una tarea con subtareas): subtasks.task_id
        // tiene cascadeOnDelete() en BD, asi que se eliminan junto con la tarea.
        $this->otorgarPermiso($this->dev, 'tasks.delete');

        $this->task->subtareas()->create([
            'titulo' => 'Subtarea existente',
            'horas' => 2,
            'creado_por' => $this->dev->id,
        ]);

        Livewire::actingAs($this->dev)
            ->test(ListaTareas::class)
            ->call('eliminar', $this->task->id);

        $this->assertDatabaseMissing('tasks', ['id' => $this->task->id]);
        $this->assertDatabaseMissing('subtasks', ['task_id' => $this->task->id]);
    }
}
