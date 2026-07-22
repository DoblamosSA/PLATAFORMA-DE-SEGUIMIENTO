<?php

namespace Tests\Unit;

use App\Domain\Organization\Exceptions\RoleHierarchyException;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\PermissionResolutionService;
use App\Domain\Organization\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionResolutionService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(PermissionResolutionService::class);
    }

    public function test_un_rol_primario_resuelve_exactamente_sus_permisos_otorgados(): void
    {
        $x = Permission::factory()->create(['slug' => 'x.view']);
        $y = Permission::factory()->create(['slug' => 'y.view']);

        $rol = Role::factory()->create(['is_primary' => true, 'parent_role_id' => null]);
        $rol->permissions()->attach([$x->id => ['tipo' => 'grant'], $y->id => ['tipo' => 'grant']]);

        $efectivo = $this->resolver->resolveEffectivePermissions($rol->fresh());

        $this->assertTrue($efectivo->has('x.view'));
        $this->assertTrue($efectivo->has('y.view'));
    }

    public function test_un_rol_heredado_agrega_y_quita_permisos_del_padre(): void
    {
        $x = Permission::factory()->create(['slug' => 'x.view']);
        $y = Permission::factory()->create(['slug' => 'y.view']);
        $z = Permission::factory()->create(['slug' => 'z.view']);

        $padre = Role::factory()->create(['is_primary' => true]);
        $padre->permissions()->attach([$x->id => ['tipo' => 'grant'], $y->id => ['tipo' => 'grant']]);

        $hijo = Role::factory()->create(['parent_role_id' => $padre->id]);
        // agrega z, quita y
        $hijo->permissions()->attach([$z->id => ['tipo' => 'grant'], $y->id => ['tipo' => 'deny']]);

        $efectivo = $this->resolver->resolveEffectivePermissions($hijo->fresh());

        $this->assertTrue($efectivo->has('x.view'), 'hereda x del padre');
        $this->assertFalse($efectivo->has('y.view'), 'y fue revocado explicitamente');
        $this->assertTrue($efectivo->has('z.view'), 'z fue agregado por el hijo');
    }

    public function test_herencia_multinivel_aplica_cada_override_en_orden(): void
    {
        $a = Permission::factory()->create(['slug' => 'a.view']);
        $b = Permission::factory()->create(['slug' => 'b.view']);

        $abuelo = Role::factory()->create(['is_primary' => true]);
        $abuelo->permissions()->attach([$a->id => ['tipo' => 'grant']]);

        $padre = Role::factory()->create(['parent_role_id' => $abuelo->id]);
        $padre->permissions()->attach([$b->id => ['tipo' => 'grant']]);

        $nieto = Role::factory()->create(['parent_role_id' => $padre->id]);
        $nieto->permissions()->attach([$a->id => ['tipo' => 'deny']]);

        $efectivo = $this->resolver->resolveEffectivePermissions($nieto->fresh());

        $this->assertFalse($efectivo->has('a.view'), 'el nieto revoca lo que otorgo el abuelo');
        $this->assertTrue($efectivo->has('b.view'), 'hereda del padre intermedio');
    }

    public function test_actualizar_el_padre_a_un_descendiente_lanza_excepcion_de_ciclo(): void
    {
        $abuelo = Role::factory()->create();
        $padre = Role::factory()->create(['parent_role_id' => $abuelo->id]);
        $nieto = Role::factory()->create(['parent_role_id' => $padre->id]);

        $service = app(RoleService::class);

        $this->expectException(RoleHierarchyException::class);

        $service->updateParent($abuelo, $nieto);
    }
}
