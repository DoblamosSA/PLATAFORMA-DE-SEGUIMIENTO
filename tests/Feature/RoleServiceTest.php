<?php

namespace Tests\Feature;

use App\Domain\Organization\DTOs\RoleData;
use App\Domain\Organization\Exceptions\RoleNotDeletableException;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\RoleService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleService::class);
    }

    public function test_crea_rol_heredado_con_permisos_agregados_y_quitados(): void
    {
        $padre = Role::factory()->create(['is_primary' => true]);
        $agregado = Permission::factory()->create(['slug' => 'custom.agregado']);
        $quitado = Permission::factory()->create(['slug' => 'custom.quitado']);
        $padre->permissions()->attach([$quitado->id => ['tipo' => 'grant']]);

        $hijo = $this->service->createInheritedRole(
            new RoleData(id: null, nombre: 'Auxiliar Contable', slug: 'auxiliar-contable', parentRoleId: $padre->id, departmentId: null),
            grantedSlugs: ['custom.agregado'],
            revokedSlugs: ['custom.quitado'],
        );

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $hijo->id, 'permission_id' => $agregado->id, 'tipo' => 'grant',
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $hijo->id, 'permission_id' => $quitado->id, 'tipo' => 'deny',
        ]);
    }

    public function test_no_permite_eliminar_un_rol_primario(): void
    {
        $rol = Role::factory()->create(['is_primary' => true, 'is_deletable' => false]);

        $this->expectException(RoleNotDeletableException::class);

        $this->service->deleteRole($rol);
    }

    public function test_no_permite_eliminar_un_rol_asignado_a_un_departamento(): void
    {
        $rol = Role::factory()->create(['is_deletable' => true]);
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $department->users()->attach($user->id, ['role_id' => $rol->id, 'es_principal' => false]);

        $this->expectException(RoleNotDeletableException::class);

        $this->service->deleteRole($rol);
    }

    public function test_permite_eliminar_un_rol_heredado_libre(): void
    {
        $rol = Role::factory()->create(['is_deletable' => true]);

        $this->service->deleteRole($rol);

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }
}
