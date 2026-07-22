<?php

namespace Database\Seeders;

use App\Domain\Organization\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Catalogo de permisos RBAC. Idempotente (firstOrCreate por slug).
 */
class PermissionSeeder extends Seeder
{
    /** @return array<int, array{slug: string, nombre: string, grupo: string}> */
    public static function catalogo(): array
    {
        return [
            ['slug' => 'projects.view', 'nombre' => 'Ver proyectos', 'grupo' => 'projects'],
            ['slug' => 'projects.create', 'nombre' => 'Crear proyectos', 'grupo' => 'projects'],
            ['slug' => 'projects.edit', 'nombre' => 'Editar proyectos', 'grupo' => 'projects'],
            ['slug' => 'projects.delete', 'nombre' => 'Eliminar proyectos', 'grupo' => 'projects'],

            ['slug' => 'departments.view', 'nombre' => 'Ver departamentos', 'grupo' => 'departments'],
            ['slug' => 'departments.create', 'nombre' => 'Crear departamentos', 'grupo' => 'departments'],
            ['slug' => 'departments.edit', 'nombre' => 'Editar departamentos', 'grupo' => 'departments'],
            ['slug' => 'departments.delete', 'nombre' => 'Eliminar departamentos', 'grupo' => 'departments'],
            ['slug' => 'departments.manage', 'nombre' => 'Administrar departamentos', 'grupo' => 'departments'],

            ['slug' => 'subdepartments.view', 'nombre' => 'Ver subdepartamentos', 'grupo' => 'subdepartments'],
            ['slug' => 'subdepartments.create', 'nombre' => 'Crear subdepartamentos', 'grupo' => 'subdepartments'],
            ['slug' => 'subdepartments.edit', 'nombre' => 'Editar subdepartamentos', 'grupo' => 'subdepartments'],
            ['slug' => 'subdepartments.delete', 'nombre' => 'Eliminar subdepartamentos', 'grupo' => 'subdepartments'],

            ['slug' => 'roles.view', 'nombre' => 'Ver roles', 'grupo' => 'roles'],
            ['slug' => 'roles.create', 'nombre' => 'Crear roles', 'grupo' => 'roles'],
            ['slug' => 'roles.edit', 'nombre' => 'Editar roles', 'grupo' => 'roles'],
            ['slug' => 'roles.delete', 'nombre' => 'Eliminar roles', 'grupo' => 'roles'],
            ['slug' => 'roles.assign', 'nombre' => 'Asignar roles', 'grupo' => 'roles'],

            ['slug' => 'users.view', 'nombre' => 'Ver usuarios', 'grupo' => 'users'],
            ['slug' => 'users.manage', 'nombre' => 'Administrar usuarios', 'grupo' => 'users'],
        ];
    }

    public function run(): void
    {
        foreach (self::catalogo() as $permiso) {
            Permission::firstOrCreate(['slug' => $permiso['slug']], $permiso);
        }
    }
}
