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

    public function test_el_campo_de_fecha_limite_no_aparece_para_un_usuario_no_admin(): void
    {
        $this->form($this->lider)->assertDontSee('Modificar fecha límite');
    }

    public function test_un_no_admin_no_puede_cambiar_la_fecha_limite_aunque_manipule_la_propiedad(): void
    {
        $original = $this->fechaOriginal();

        $this->form($this->lider)
            ->set('fechaLimiteInput', now()->addMonth()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($original, $this->fechaOriginal());
    }

    public function test_el_admin_debe_dejar_observacion_al_cambiar_la_fecha_limite(): void
    {
        $original = $this->fechaOriginal();
        $nueva = now()->addDays(5)->format('Y-m-d\TH:i');

        $this->form($this->admin)
            ->set('fechaLimiteInput', $nueva)
            ->call('save')
            ->assertHasErrors(['observacionFecha' => 'required']);

        // No se persiste el cambio sin la observacion
        $this->assertSame($original, $this->fechaOriginal());
    }

    public function test_el_admin_puede_cambiar_la_fecha_limite_dejando_observacion(): void
    {
        $nueva = now()->addDays(5)->startOfMinute();

        $this->form($this->admin)
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

        $this->form($this->admin)
            ->set('fechaLimiteInput', $nuevaFecha->format('Y-m-d\TH:i'))
            ->set('observacionFecha', 'Correccion retroactiva del plazo acordado con el cliente.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->task->fresh()->cumplida_a_tiempo);
    }

    public function test_el_admin_puede_dejar_la_tarea_sin_fecha_limite(): void
    {
        $this->form($this->admin)
            ->set('fechaLimiteInput', null)
            ->set('observacionFecha', 'Se retira el vencimiento por acuerdo especial.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->task->fresh()->fecha_limite);
    }
}
