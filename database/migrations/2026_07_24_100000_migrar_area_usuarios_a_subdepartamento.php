<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migracion de datos: lo que antes era el campo enum `users.area`
 * (software/soporte/infraestructura/general) ahora se modela como el
 * subdepartamento del colaborador (relacion pivote sub_department_user).
 *
 * Backfill best-effort: a cada colaborador con un `area` reconocida se le
 * asigna el subdepartamento equivalente (por slug) bajo el departamento
 * "Tecnologia" — los mismos subdepartamentos que ya creo la migracion de
 * tareas (2026_07_22_150200). Reglas:
 *   - 'general' no tiene subdepartamento equivalente: se deja sin asignar.
 *   - Solo se toca a quien AUN no tiene ningun subdepartamento (no pisa
 *     asignaciones hechas manualmente con el formulario nuevo).
 * La columna `users.area` NO se elimina aqui (queda inactiva/legacy); se
 * puede quitar mas adelante en una migracion aparte si se desea.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Si la columna legacy ya no existe, no hay nada que migrar.
        if (! Schema::hasColumn('users', 'area')) {
            return;
        }

        $tecnologiaId = DB::table('departments')->where('slug', 'tecnologia')->value('id');

        // Sin el departamento "Tecnologia" no existen los subdepartamentos destino.
        if (! $tecnologiaId) {
            return;
        }

        // area (enum viejo) => slug del subdepartamento (bajo Tecnologia).
        $mapa = [
            'software' => 'software',
            'soporte' => 'soporte',
            'infraestructura' => 'infraestructura',
        ];

        $subIdPorSlug = DB::table('sub_departments')
            ->where('department_id', $tecnologiaId)
            ->whereIn('slug', array_values($mapa))
            ->pluck('id', 'slug');

        $ahora = now();

        foreach ($mapa as $area => $slug) {
            $subDepartmentId = $subIdPorSlug[$slug] ?? null;

            // Ese subdepartamento no existe en esta instalacion: nada que asignar.
            if (! $subDepartmentId) {
                continue;
            }

            // Colaboradores con esa area que todavia no tienen ningun
            // subdepartamento asignado (no sobrescribimos asignaciones nuevas).
            $usuarios = DB::table('users')
                ->where('area', $area)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('sub_department_user')
                        ->whereColumn('sub_department_user.user_id', 'users.id');
                })
                ->pluck('id');

            $filas = $usuarios->map(fn ($userId) => [
                'sub_department_id' => $subDepartmentId,
                'user_id' => $userId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all();

            if (! empty($filas)) {
                DB::table('sub_department_user')->insert($filas);
            }
        }
    }

    public function down(): void
    {
        // Migracion de datos best-effort: no se revierte automaticamente para
        // no eliminar asignaciones de subdepartamento que pudieran haberse
        // hecho manualmente despues de correrla.
    }
};
