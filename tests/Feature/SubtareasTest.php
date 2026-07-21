<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\TableroProyecto;
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

        $this->lider = User::factory()->create(['rol' => 'lider']);
        $this->dev = User::factory()->create(['rol' => 'tecnico']);
        $this->ajeno = User::factory()->create(['rol' => 'tecnico']);

        $this->project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'alta',
            'responsable_id' => $this->lider->id,
        ]);

        $this->project->equipo()->attach($this->dev->id, ['rol_en_proyecto' => 'desarrollador']);

        $this->task = Task::create([
            'project_id' => $this->project->id,
            'titulo' => 'Tarea con subtareas',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
        ]);
        $this->task->aplicarSla();
        $this->task->save();
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

    public function test_las_subtareas_se_mantienen_en_orden_de_creacion(): void
    {
        $componente = Livewire::actingAs($this->lider)
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
}
