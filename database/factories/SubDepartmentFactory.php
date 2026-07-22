<?php

namespace Database\Factories;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Organization\Models\SubDepartment>
 */
class SubDepartmentFactory extends Factory
{
    protected $model = SubDepartment::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->word();

        return [
            'department_id' => Department::factory(),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => fake()->sentence(),
            'activo' => true,
        ];
    }
}
