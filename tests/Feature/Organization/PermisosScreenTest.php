<?php

namespace Tests\Feature\Organization;

use App\Domain\Organization\Models\Permission;
use App\Livewire\Organization\Permisos\ListaPermisos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermisosScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_sin_permiso_no_puede_ver_el_catalogo(): void
    {
        $user = User::factory()->create(['rol' => 'tecnico']);

        $this->actingAs($user)->get(route('permisos'))->assertForbidden();
    }

    public function test_el_superadmin_ve_el_catalogo_agrupado(): void
    {
        $superAdmin = User::factory()->create(['rol' => 'admin']);
        Permission::factory()->create(['slug' => 'departments.manage', 'nombre' => 'Administrar departamentos', 'grupo' => 'departments']);

        Livewire::actingAs($superAdmin)
            ->test(ListaPermisos::class)
            ->assertSee('Administrar departamentos')
            ->assertSee('departments.manage');
    }
}
