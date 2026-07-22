<?php

namespace Database\Seeders;

use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Los 5 roles primarios (globales, no eliminables). Los slugs de
 * admin/lider/tecnico/evaluador coinciden con los valores actuales de
 * users.rol para que el mapeo entre ambos sistemas sea directo.
 * Depende de PermissionSeeder.
 */
class RoleSeeder extends Seeder
{
    private const ROLES_PRIMARIOS = [
        'super-admin' => 'Super Administrador',
        'admin' => 'Administrador',
        'lider' => 'Coordinador',
        'tecnico' => 'Colaborador',
        'evaluador' => 'Evaluador',
    ];

    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $roles = [];

        foreach (self::ROLES_PRIMARIOS as $slug => $nombre) {
            $roles[$slug] = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'nombre' => $nombre,
                    'parent_role_id' => null,
                    'department_id' => null,
                    'is_primary' => true,
                    'is_deletable' => false,
                ],
            );
        }

        $todosLosPermisos = Permission::pluck('id')->all();
        $this->otorgar($roles['super-admin'], $todosLosPermisos);
        $this->otorgar($roles['admin'], $todosLosPermisos);

        $this->otorgar($roles['lider'], Permission::whereIn('slug', [
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
            'departments.view',
        ])->pluck('id')->all());

        $this->otorgar($roles['tecnico'], Permission::whereIn('slug', ['projects.view'])->pluck('id')->all());
        $this->otorgar($roles['evaluador'], Permission::whereIn('slug', ['projects.view'])->pluck('id')->all());
    }

    /** @param  array<int, int>  $permissionIds */
    private function otorgar(Role $role, array $permissionIds): void
    {
        $sync = collect($permissionIds)->mapWithKeys(fn ($id) => [$id => ['tipo' => 'grant']])->all();
        $role->permissions()->syncWithoutDetaching($sync);
    }
}
