<?php

namespace Database\Factories;

use App\Domain\Organization\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Organization\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $slug = fake()->unique()->word().'.'.fake()->randomElement(['view', 'create', 'edit', 'delete']);

        return [
            'slug' => $slug,
            'nombre' => ucfirst(str_replace('.', ' ', $slug)),
            'descripcion' => fake()->sentence(),
            'grupo' => fake()->word(),
        ];
    }
}
