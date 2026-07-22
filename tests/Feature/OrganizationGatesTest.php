<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OrganizationGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_pasa_cualquier_gate_via_before(): void
    {
        $superAdminRole = Role::factory()->create(['slug' => 'super-admin', 'is_primary' => true, 'is_deletable' => false]);
        $user = User::factory()->create(['rol' => 'tecnico']);
        $user->rolesGlobales()->attach($superAdminRole->id);

        $this->assertTrue(Gate::forUser($user)->allows('roles.manage'));
        $this->assertTrue(Gate::forUser($user)->allows('cualquier-cosa-inventada'));
    }

    public function test_usuario_con_permiso_especifico_pasa_solo_ese_gate(): void
    {
        $rolesPermission = Permission::factory()->create(['slug' => 'roles.manage']);
        $role = Role::factory()->create();
        $role->permissions()->attach($rolesPermission->id, ['tipo' => 'grant']);

        $department = \App\Domain\Organization\Models\Department::factory()->create();
        $user = User::factory()->create(['rol' => 'tecnico']);
        $department->users()->attach($user->id, ['role_id' => $role->id, 'es_principal' => true]);

        $this->assertTrue(Gate::forUser($user)->allows('roles.manage'));
        $this->assertFalse(Gate::forUser($user)->allows('users.manage'));
    }

    public function test_el_gate_admin_existente_sigue_funcionando_igual(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $noAdmin = User::factory()->create(['rol' => 'tecnico']);

        $this->assertTrue(Gate::forUser($admin)->allows('admin'));
        $this->assertFalse(Gate::forUser($noAdmin)->allows('admin'));
    }
}
