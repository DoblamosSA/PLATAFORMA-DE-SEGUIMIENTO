<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\DepartmentService;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentUserAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_asigna_un_usuario_a_un_departamento_con_rol_y_es_principal(): void
    {
        $department = Department::factory()->create();
        $role = Role::factory()->create();
        $user = User::factory()->create();

        app(DepartmentService::class)->assignUserToDepartment($user, $department, $role, esPrincipal: true);

        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'es_principal' => true,
        ]);
    }

    public function test_un_usuario_no_puede_repetirse_en_el_mismo_departamento(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $department->users()->attach($user->id, ['es_principal' => false]);

        $this->expectException(QueryException::class);

        $department->users()->attach($user->id, ['es_principal' => false]);
    }

    public function test_remueve_un_usuario_de_un_departamento(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $service = app(DepartmentService::class);

        $service->assignUserToDepartment($user, $department);
        $service->removeUserFromDepartment($user, $department);

        $this->assertDatabaseMissing('department_user', [
            'department_id' => $department->id,
            'user_id' => $user->id,
        ]);
    }
}
