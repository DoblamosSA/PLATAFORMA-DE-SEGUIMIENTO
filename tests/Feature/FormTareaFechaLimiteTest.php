<?php

namespace Tests\Feature;

use App\Livewire\Tareas\FormTarea;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormTareaFechaLimiteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $lider;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->lider = User::factory()->create(['rol' => 'lider']);

        $project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'software',
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);

        $this->task = Task::create([
            'project_id' => $project->id,
            'titulo' => 'Tarea original',
            'tipo' => 'software',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
        ]);
        $this->task->aplicarSla();
        $this->task->save();
    }

    private function fechaOriginal(): string
    {
        return $this->task->fresh()->fecha_limite->format('Y-m-d\TH:i');
    }

    public function test_el_campo_de_fecha_limite_no_aparece_para_un_usuario_no_admin(): void
    {
        Livewire::actingAs($this->lider)
            ->test(FormTarea::class, ['task' => $this->task])
            ->assertDontSee('Modificar fecha límite');
    }

    public function test_un_no_admin_no_puede_cambiar_la_fecha_limite_aunque_manipule_la_propiedad(): void
    {
        $original = $this->fechaOriginal();

        Livewire::actingAs($this->lider)
            ->test(FormTarea::class, ['task' => $this->task])
            ->set('fechaLimiteInput', now()->addMonth()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($original, $this->fechaOriginal());
    }

    public function test_el_admin_debe_dejar_observacion_al_cambiar_la_fecha_limite(): void
    {
        $original = $this->fechaOriginal();
        $nueva = now()->addDays(5)->format('Y-m-d\TH:i');

        Livewire::actingAs($this->admin)
            ->test(FormTarea::class, ['task' => $this->task])
            ->set('fechaLimiteInput', $nueva)
            ->call('save')
            ->assertHasErrors(['observacionFecha' => 'required']);

        // No se persiste el cambio sin la observacion
        $this->assertSame($original, $this->fechaOriginal());
    }

    public function test_el_admin_puede_cambiar_la_fecha_limite_dejando_observacion(): void
    {
        $nueva = now()->addDays(5)->startOfMinute();

        Livewire::actingAs($this->admin)
            ->test(FormTarea::class, ['task' => $this->task])
            ->set('fechaLimiteInput', $nueva->format('Y-m-d\TH:i'))
            ->set('observacionFecha', 'El cliente solicito una prorroga de 5 dias.')
            ->call('save')
            ->assertHasNoErrors();

        $this->task->refresh();
        $this->assertTrue($nueva->equalTo($this->task->fecha_limite));

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $this->task->id,
            'accion' => 'cambio_fecha_limite',
        ]);

        $actividad = $this->task->actividades()->where('accion', 'cambio_fecha_limite')->first();
        $this->assertStringContainsString('Motivo: El cliente solicito una prorroga de 5 dias.', $actividad->detalle);
    }

    public function test_cambiar_la_fecha_limite_de_una_tarea_ya_cerrada_reevalua_el_cumplimiento(): void
    {
        // La tarea se completa a tiempo con la fecha limite original
        $this->task->completar(now());
        $this->assertTrue($this->task->fresh()->cumplida_a_tiempo);

        // El admin adelanta la fecha limite a un momento anterior al cierre real
        $nuevaFecha = $this->task->fresh()->fecha_completada->subDay();

        Livewire::actingAs($this->admin)
            ->test(FormTarea::class, ['task' => $this->task])
            ->set('fechaLimiteInput', $nuevaFecha->format('Y-m-d\TH:i'))
            ->set('observacionFecha', 'Correccion retroactiva del SLA acordado con el cliente.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->task->fresh()->cumplida_a_tiempo);
    }

    public function test_el_admin_puede_dejar_la_tarea_sin_fecha_limite(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FormTarea::class, ['task' => $this->task])
            ->set('fechaLimiteInput', null)
            ->set('observacionFecha', 'Se retira el SLA por acuerdo especial.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->task->fresh()->fecha_limite);
    }
}
