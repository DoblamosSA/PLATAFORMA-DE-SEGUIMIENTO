<?php

namespace Tests\Feature;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Services\DepartmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepartmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DepartmentService::class);
    }

    public function test_crea_un_departamento(): void
    {
        $data = new DepartmentData(id: null, nombre: 'Tecnologia', slug: 'tecnologia', descripcion: 'TI', activo: true);

        $department = $this->service->create($data);

        $this->assertDatabaseHas('departments', ['slug' => 'tecnologia', 'nombre' => 'Tecnologia']);
        $this->assertTrue($department->activo);
    }

    public function test_actualiza_un_departamento(): void
    {
        $department = Department::factory()->create(['nombre' => 'Original']);

        $actualizado = $this->service->update($department, new DepartmentData(
            id: $department->id,
            nombre: 'Renombrado',
            slug: $department->slug,
            descripcion: $department->descripcion,
            activo: $department->activo,
        ));

        $this->assertSame('Renombrado', $actualizado->nombre);
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'nombre' => 'Renombrado']);
    }

    public function test_eliminar_un_departamento_es_soft_delete(): void
    {
        $department = Department::factory()->create();

        $this->service->delete($department);

        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }
}
