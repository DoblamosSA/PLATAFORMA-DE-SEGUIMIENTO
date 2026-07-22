<?php

namespace Tests\Feature;

use App\Domain\Organization\DTOs\SubDepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Services\SubDepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubDepartmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubDepartmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SubDepartmentService::class);
    }

    public function test_crea_un_subdepartamento_bajo_un_departamento(): void
    {
        $department = Department::factory()->create();

        $subDepartment = $this->service->create(new SubDepartmentData(
            id: null,
            departmentId: $department->id,
            nombre: 'Software',
            slug: 'software',
            descripcion: null,
            activo: true,
        ));

        $this->assertDatabaseHas('sub_departments', [
            'department_id' => $department->id,
            'slug' => 'software',
        ]);
        $this->assertTrue($subDepartment->department->is($department));
    }

    public function test_rechaza_mover_un_subdepartamento_a_otro_departamento_via_update(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();
        $subDepartment = SubDepartment::factory()->create(['department_id' => $departmentA->id]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->update($subDepartment, new SubDepartmentData(
            id: $subDepartment->id,
            departmentId: $departmentB->id,
            nombre: $subDepartment->nombre,
            slug: $subDepartment->slug,
            descripcion: $subDepartment->descripcion,
            activo: $subDepartment->activo,
        ));
    }

    public function test_eliminar_un_subdepartamento_es_soft_delete(): void
    {
        $subDepartment = SubDepartment::factory()->create();

        $this->service->delete($subDepartment);

        $this->assertSoftDeleted('sub_departments', ['id' => $subDepartment->id]);
    }
}
