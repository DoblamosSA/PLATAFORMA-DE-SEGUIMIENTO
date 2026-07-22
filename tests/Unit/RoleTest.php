<?php

namespace Tests\Unit;

use App\Domain\Organization\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_relacion_parent_y_children(): void
    {
        $padre = Role::factory()->create();
        $hijo = Role::factory()->create(['parent_role_id' => $padre->id]);

        $this->assertTrue($hijo->parent->is($padre));
        $this->assertTrue($padre->children->contains($hijo));
    }

    public function test_defaults_de_is_primary_e_is_deletable(): void
    {
        $rol = Role::factory()->create();

        $this->assertFalse($rol->is_primary);
        $this->assertTrue($rol->is_deletable);
    }
}
