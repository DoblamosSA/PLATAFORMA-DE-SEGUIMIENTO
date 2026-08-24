<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
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

    private SubDepartment $subDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->lider = User::factory()->create(['rol' => 'lider']);

        $department = Department::factory()->create();
        $this->subDepartment = SubDepartment::factory()->create(['department_id' => $department->id]);
        $department->users()->attach($this->lider->id, ['es_principal' => true]);

        $this->otorgarPermiso($this->lider, 'tasks.edit');
        $this->otorgarPermiso($this->admin, 'tasks.edit');

        $project = Project::create([
            'nombre' => 'Proyecto de prueba',
            'sub_department_id' => $this->subDepartment->id,
            'estado' => 'en_progreso',
            'prioridad' => 'media',
        ]);

        $this->task = Task::create([
            'project_id' => $project->id,
            'titulo' => 'Tarea original',
            'sub_department_id' => $this->subDepartment->id,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
            'fecha_inicio' => now(),
            'fecha_limite' => now()->addDays(3),
        ]);
    }

    private function otorgarPermiso(User $user, string $slug): void
    {
        $permiso = Permission::firstOrCreate(
            ['slug' => $slug],
            ['nombre' => $slug, 'grupo' => 'tasks'],
        );
        $rol = Role::factory()->create(['is_primary' => true]);
        $rol->permissions()->syncWithoutDetaching([$permiso->id => ['tipo' => 'grant']]);

        $depto = $user->departments()->first();
        if (! $depto) {
            $depto = Department::factory()->create();
            $depto->users()->attach($user->id, ['es_principal' => true, 'role_id' => $rol->id]);

            return;
        }

        $user->departments()->updateExistingPivot($depto->id, ['role_id' => $rol->id]);
    }

    private function form(User $user)
    {
        return Livewire::actingAs($user)
            ->test(FormTarea::class, ['task' => $this->task, 'enModal' => true]);
    }

    private function fechaOriginal(): string
    {
        return $this->task->fresh()->fecha_limite->format('Y-m-d\TH:i');
    }

    public function test_fecha_inicio_es_obligatoria_para_cualquier_usuario_con_permiso(): void
    {
        $this->form($this->lider)
            ->set('fechaInicioInput', '')
            ->call('save')
            ->assertHasErrors(['fechaInicioInput' => 'required']);
    }

    public function test_fecha_limite_es_obligatoria_para_cualquier_usuario_con_permiso(): void
    {
        $this->form($this->lider)
            ->set('fechaLimiteInput', '')
            ->call('save')
            ->assertHasErrors(['fechaLimiteInput' => 'required']);
    }

    public function test_fecha_limite_no_puede_ser_anterior_a_fecha_inicio(): void
    {
        $this->form($this->lider)
            ->set('fechaInicioInput', now()->addDays(5)->format('Y-m-d'))
            ->set('fechaLimiteInput', now()->addDays(1)->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors(['fechaLimiteInput' => 'after_or_equal']);
    }

    public function test_un_usuario_sin_rol_admin_puede_cambiar_la_fecha_limite_de_una_tarea_abierta(): void
    {
        $nueva = now()->addMonth()->startOfMinute();

        $this->form($this->lider)
            ->set('fechaLimiteInput', $nueva->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($nueva->equalTo($this->task->fresh()->fecha_limite));
    }

    public function test_cambiar_fecha_limite_de_una_tarea_ya_cerrada_exige_observacion_sin_importar_el_rol(): void
    {
        $this->task->completar(now());
        $original = $this->fechaOriginal();
        $nueva = now()->addDays(5)->format('Y-m-d\TH:i');

        $this->form($this->lider)
            ->set('fechaLimiteInput', $nueva)
            ->call('save')
            ->assertHasErrors(['observacionFecha' => 'required']);

        // No se persiste el cambio sin la observacion
        $this->assertSame($original, $this->fechaOriginal());
    }

    public function test_usuario_puede_cambiar_fecha_limite_de_tarea_cerrada_dejando_observacion(): void
    {
        $this->task->completar(now());
        $nueva = now()->addDays(5)->startOfMinute();

        $this->form($this->lider)
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

        // Se adelanta la fecha limite a un momento anterior al cierre real
        $nuevaFecha = $this->task->fresh()->fecha_completada->subDay();

        $this->form($this->admin)
            ->set('fechaInicioInput', $nuevaFecha->copy()->subDay()->format('Y-m-d'))
            ->set('fechaLimiteInput', $nuevaFecha->format('Y-m-d\TH:i'))
            ->set('observacionFecha', 'Correccion retroactiva del plazo acordado con el cliente.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->task->fresh()->cumplida_a_tiempo);
    }
}
