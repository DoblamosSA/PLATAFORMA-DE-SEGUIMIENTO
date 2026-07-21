<?php

namespace Tests\Feature;

use App\Livewire\Tareas\FormTarea;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Permisos por rol sobre tareas: creacion, eliminacion y la restriccion de
 * tag obligatorio para el evaluador. Se prueba tanto el acceso HTTP (mount)
 * como la accion en si (save/eliminar), ya que la UI solo oculta botones.
 */
class PermisosTareasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $coordinador;

    private User $colaborador;

    private User $evaluador;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->coordinador = User::factory()->create(['rol' => 'lider']);
        $this->colaborador = User::factory()->create(['rol' => 'tecnico']);
        $this->evaluador = User::factory()->create(['rol' => 'evaluador']);

        $this->project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'alta',
            'responsable_id' => $this->coordinador->id,
        ]);

        $this->project->equipo()->attach([
            $this->colaborador->id => ['rol_en_proyecto' => 'desarrollador'],
            $this->evaluador->id => ['rol_en_proyecto' => 'evaluador'],
        ]);
    }

    public function test_un_colaborador_no_puede_acceder_al_formulario_de_nueva_tarea(): void
    {
        $this->actingAs($this->colaborador)
            ->get(route('tareas.crear'))
            ->assertForbidden();
    }

    public function test_un_coordinador_puede_crear_una_tarea(): void
    {
        Livewire::actingAs($this->coordinador)
            ->test(FormTarea::class)
            ->set('titulo', 'Nueva tarea de coordinador')
            ->set('tipo', 'software')
            ->set('project_id', $this->project->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['titulo' => 'Nueva tarea de coordinador']);
    }

    public function test_un_evaluador_solo_puede_crear_tareas_con_tag_certificacion_bloqueado(): void
    {
        Livewire::actingAs($this->evaluador)
            ->test(FormTarea::class)
            ->assertSet('tag', 'certificacion')
            ->assertSet('tagBloqueado', true)
            ->set('titulo', 'Certificacion de entrega')
            ->set('tipo', 'software')
            ->set('project_id', $this->project->id)
            // Intenta manipular el tag desde el cliente: el servidor debe ignorarlo.
            ->set('tag', 'otra-cosa')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'titulo' => 'Certificacion de entrega',
            'tag' => 'certificacion',
        ]);
    }

    public function test_un_colaborador_no_puede_montar_el_formulario_de_creacion(): void
    {
        // Defensa en el servidor: el bloqueo ocurre en mount(), antes de
        // que pueda intentar guardar nada.
        Livewire::actingAs($this->colaborador)
            ->test(FormTarea::class)
            ->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_un_coordinador_puede_eliminar_una_tarea_sin_subtareas(): void
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea sin subtareas',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'creado_por' => $this->coordinador->id,
            'fecha_asignacion' => now(),
        ]);

        Livewire::actingAs($this->coordinador)
            ->test(FormTarea::class, ['task' => $task])
            ->assertSet('puedeEliminar', true)
            ->call('eliminar');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_un_coordinador_no_puede_eliminar_una_tarea_con_subtareas(): void
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea con subtareas',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'creado_por' => $this->coordinador->id,
            'fecha_asignacion' => now(),
        ]);
        $task->subtareas()->create(['titulo' => 'Sub 1', 'horas' => 2, 'creado_por' => $this->coordinador->id]);

        Livewire::actingAs($this->coordinador)
            ->test(FormTarea::class, ['task' => $task])
            ->assertSet('puedeEliminar', false)
            ->call('eliminar')
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_un_admin_siempre_puede_eliminar_una_tarea_con_subtareas(): void
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea con subtareas',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'creado_por' => $this->coordinador->id,
            'fecha_asignacion' => now(),
        ]);
        $task->subtareas()->create(['titulo' => 'Sub 1', 'horas' => 2, 'creado_por' => $this->coordinador->id]);

        Livewire::actingAs($this->admin)
            ->test(FormTarea::class, ['task' => $task])
            ->call('eliminar');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
