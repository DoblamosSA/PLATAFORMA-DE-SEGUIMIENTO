<?php

namespace Tests\Feature\Organization;

use App\Domain\Organization\Models\Department;
use App\Livewire\Organization\Departamentos\FormDepartamento;
use App\Livewire\Organization\Departamentos\ListaDepartamentos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartamentosScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $sinPermiso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['rol' => 'admin']);
        $this->sinPermiso = User::factory()->create(['rol' => 'tecnico']);
    }

    public function test_un_usuario_sin_permiso_no_puede_ver_el_listado(): void
    {
        $this->actingAs($this->sinPermiso)->get(route('departamentos'))->assertForbidden();
    }

    public function test_el_superadmin_ve_el_listado(): void
    {
        Department::factory()->create(['nombre' => 'Tecnología']);

        Livewire::actingAs($this->superAdmin)
            ->test(ListaDepartamentos::class)
            ->assertSee('Tecnología');
    }

    public function test_el_superadmin_crea_un_departamento(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(FormDepartamento::class)
            ->set('nombre', 'Financiera')
            ->set('descripcion', 'Compras, facturación y contabilidad')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', ['nombre' => 'Financiera']);
    }

    public function test_no_permite_nombre_duplicado(): void
    {
        Department::factory()->create(['nombre' => 'TI']);

        Livewire::actingAs($this->superAdmin)
            ->test(FormDepartamento::class)
            ->set('nombre', 'TI')
            ->call('save')
            ->assertHasErrors(['nombre']);
    }

    public function test_el_superadmin_edita_un_departamento(): void
    {
        $department = Department::factory()->create(['nombre' => 'Original']);

        Livewire::actingAs($this->superAdmin)
            ->test(FormDepartamento::class, ['department' => $department])
            ->set('nombre', 'Renombrado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'nombre' => 'Renombrado']);
    }

    public function test_el_superadmin_elimina_un_departamento(): void
    {
        $department = Department::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test(ListaDepartamentos::class)
            ->call('eliminar', $department->id);

        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }
}
