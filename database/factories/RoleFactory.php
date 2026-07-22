<?php

namespace Database\Factories;

use App\Domain\Organization\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Organization\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->jobTitle();

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.fake()->unique()->numberBetween(1000, 9999),
            'parent_role_id' => null,
            'department_id' => null,
            'is_primary' => false,
            'is_deletable' => true,
        ];
    }
}
