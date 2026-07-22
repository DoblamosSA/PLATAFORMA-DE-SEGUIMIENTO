<?php

namespace Database\Seeders;

use App\Domain\Organization\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Asigna el rol global 'super-admin' a todo usuario existente con
 * rol = 'admin'. Idempotente (syncWithoutDetaching): correrlo varias
 * veces deja exactamente una fila en user_roles por admin. No altera
 * la columna users.rol. Ejecutable de forma independiente contra la
 * BD real:
 *   php artisan db:seed --class="Database\Seeders\SuperAdminBackfillSeeder"
 */
class SuperAdminBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('slug', 'super-admin')->first();

        if (! $superAdmin) {
            return; // RoleSeeder no ha corrido todavia; nada que hacer
        }

        User::where('rol', 'admin')->get()->each(
            fn (User $user) => $user->rolesGlobales()->syncWithoutDetaching([$superAdmin->id]),
        );
    }
}
