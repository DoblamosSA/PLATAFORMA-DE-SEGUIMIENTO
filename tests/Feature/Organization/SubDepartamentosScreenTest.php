<?php

namespace Tests\Feature\Organization;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Livewire\Organization\SubDepartamentos\FormSubDepartamento;
use App\Livewire\Organization\SubDepartamentos\ListaSubDepartamentos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubDepartamentosScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['rol' => 'admin']);
    }

    public function test_un_usuario_sin_permiso_no_puede_ver_el_listado(): void
    {
        $user = User::factory()->create(['rol' => 'tecnico']);

        $this->actingAs($user)->get(route('subdepartamentos'))->assertForbidden();
    }

    public function test_el_superadmin_crea_un_subdepartamento(): void
    {
        $department = Department::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test(FormSubDepartamento::class)
            ->set('department_id', (string) $department->id)
            ->set('nombre', 'Software')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sub_departments', ['nombre' => 'Software', 'department_id' => $department->id]);
    }

    public function test_el_listado_filtra_por_departamento(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();
        SubDepartment::factory()->create(['department_id' => $departmentA->id, 'nombre' => 'Soporte']);
        SubDepartment::factory()->create(['department_id' => $departmentB->id, 'nombre' => 'Compras']);

        Livewire::actingAs($this->superAdmin)
            ->test(ListaSubDepartamentos::class)
            ->set('departamento', (string) $departmentA->id)
            ->assertSee('Soporte')
            ->assertDontSee('Compras');
    }

    public function test_el_superadmin_elimina_un_subdepartamento(): void
    {
        $subDepartment = SubDepartment::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test(ListaSubDepartamentos::class)
            ->call('eliminar', $subDepartment->id);

        $this->assertSoftDeleted('sub_departments', ['id' => $subDepartment->id]);
    }
}
