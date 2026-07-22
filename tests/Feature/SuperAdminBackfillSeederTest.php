<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminBackfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminBackfillSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_es_idempotente_para_usuarios_admin(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['rol' => 'admin']);
        User::factory()->create(['rol' => 'lider']);

        $this->seed(SuperAdminBackfillSeeder::class);
        $this->seed(SuperAdminBackfillSeeder::class); // segunda corrida: debe seguir siendo 1 fila

        $superAdminRoleId = \App\Domain\Organization\Models\Role::where('slug', 'super-admin')->value('id');

        $this->assertDatabaseCount('user_roles', 1);
        $this->assertDatabaseHas('user_roles', ['user_id' => $admin->id, 'role_id' => $superAdminRoleId]);
    }

    public function test_un_usuario_no_admin_no_recibe_super_admin(): void
    {
        $this->seed(RoleSeeder::class);

        $lider = User::factory()->create(['rol' => 'lider']);

        $this->seed(SuperAdminBackfillSeeder::class);

        $this->assertDatabaseMissing('user_roles', ['user_id' => $lider->id]);
    }
}
