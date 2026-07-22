<?php

namespace Tests\Feature\Organization;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Livewire\Colaboradores\FormColaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Usuarios" del pedido original = extension de Colaboradores (no una pantalla
 * duplicada): el formulario existente ahora tambien asigna departamento+rol.
 */
class ColaboradorDepartmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_asigna_departamento_y_rol_al_crear_un_colaborador(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $department = Department::factory()->create();
        $role = Role::factory()->create(['is_primary' => true]);

        Livewire::actingAs($admin)
            ->test(FormColaborador::class)
            ->set('name', 'Nuevo Colaborador')
            ->set('email', 'nuevo@doblamos.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('diasLaborales', ['L', 'M'])
            ->set('horasDiarias', '8')
            ->set('department_id', (string) $department->id)
            ->set('role_id', (string) $role->id)
            ->call('save')
            ->assertHasNoErrors();

        $colaborador = User::where('email', 'nuevo@doblamos.com')->firstOrFail();

        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $colaborador->id,
            'role_id' => $role->id,
            'es_principal' => true,
        ]);
    }

    public function test_no_asigna_nada_si_no_se_elige_departamento(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        Livewire::actingAs($admin)
            ->test(FormColaborador::class)
            ->set('name', 'Sin Departamento')
            ->set('email', 'sindepto@doblamos.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('diasLaborales', ['L'])
            ->set('horasDiarias', '4')
            ->call('save')
            ->assertHasNoErrors();

        $colaborador = User::where('email', 'sindepto@doblamos.com')->firstOrFail();

        $this->assertDatabaseCount('department_user', 0);
        $this->assertDatabaseMissing('department_user', ['user_id' => $colaborador->id]);
    }
}
