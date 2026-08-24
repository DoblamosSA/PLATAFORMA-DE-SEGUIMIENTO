<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_policies_ya_no_existe_como_tabla(): void
    {
        $this->assertFalse(Schema::hasTable('sla_policies'));
    }

    public function test_tasks_ya_no_tiene_la_columna_sla_horas(): void
    {
        $this->assertFalse(Schema::hasColumn('tasks', 'sla_horas'));
    }

    public function test_el_seeder_corre_sin_errores_tras_quitar_seedslapolicies(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, \App\Models\User::count());
        $this->assertGreaterThan(0, \App\Domain\Organization\Models\SubDepartment::count());
    }
}
