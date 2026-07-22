<?php

namespace Tests\Feature\Organization;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Livewire\Organization\Roles\FormRole;
use App\Livewire\Organization\Roles\ListaRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesScreenTest extends TestCase
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

        $this->actingAs($user)->get(route('roles'))->assertForbidden();
    }

    public function test_crea_un_rol_heredado_con_permisos_agregados_y_quitados(): void
    {
        $padre = Role::factory()->create(['is_primary' => true]);
        $heredado = Permission::factory()->create(['slug' => 'x.heredado']);
        $agregado = Permission::factory()->create(['slug' => 'x.agregado']);
        $padre->permissions()->attach($heredado->id, ['tipo' => 'grant']);
        $department = Department::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test(FormRole::class)
            ->set('nombre', 'Auxiliar Contable')
            ->set('parent_role_id', (string) $padre->id)
            ->set('department_id', (string) $department->id)
            ->set("overrides.{$heredado->id}", 'deny')
            ->set("overrides.{$agregado->id}", 'grant')
            ->call('save')
            ->assertHasNoErrors();

        $hijo = Role::where('nombre', 'Auxiliar Contable')->firstOrFail();
        $this->assertDatabaseHas('role_permissions', ['role_id' => $hijo->id, 'permission_id' => $heredado->id, 'tipo' => 'deny']);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $hijo->id, 'permission_id' => $agregado->id, 'tipo' => 'grant']);
    }

    public function test_un_rol_primario_se_muestra_de_solo_lectura(): void
    {
        $primario = Role::factory()->create(['is_primary' => true, 'is_deletable' => false]);

        Livewire::actingAs($this->superAdmin)
            ->test(FormRole::class, ['role' => $primario])
            ->assertSet('soloLectura', true);
    }

    public function test_no_permite_eliminar_un_rol_primario_desde_la_pantalla(): void
    {
        $primario = Role::factory()->create(['is_primary' => true, 'is_deletable' => false]);

        Livewire::actingAs($this->superAdmin)
            ->test(ListaRoles::class)
            ->call('eliminar', $primario->id);

        $this->assertDatabaseHas('roles', ['id' => $primario->id]);
    }

    public function test_duplicar_crea_una_copia_editable(): void
    {
        $padre = Role::factory()->create(['is_primary' => true]);
        $original = Role::factory()->create(['parent_role_id' => $padre->id, 'nombre' => 'Original']);

        Livewire::actingAs($this->superAdmin)
            ->test(ListaRoles::class)
            ->call('duplicar', $original->id);

        $this->assertDatabaseHas('roles', ['nombre' => 'Original (copia)', 'is_primary' => false, 'is_deletable' => true]);
    }
}
