<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de fecha_inicio/fecha_limite en tareas historicas antes de poder
 * volver esas columnas NOT NULL (ver
 * 2026_08_24_100200_make_task_dates_required_on_tasks_table). Valor por
 * defecto documentado aqui, no es una politica de negocio nueva: solo evita
 * dejar historicos con NULL.
 *
 * - fecha_inicio nula -> fecha_asignacion (o created_at si tampoco hay), solo fecha.
 * - fecha_limite nula -> fecha_inicio (ya backfillada) + 3 dias a las 17:00.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tasks')
            ->whereNull('fecha_inicio')
            ->orderBy('id')
            ->chunkById(200, function ($tareas) {
                foreach ($tareas as $tarea) {
                    $base = $tarea->fecha_asignacion ?? $tarea->created_at;
                    $fechaInicio = Carbon::parse($base)->toDateString();

                    DB::table('tasks')->where('id', $tarea->id)->update([
                        'fecha_inicio' => $fechaInicio,
                    ]);
                }
            });

        DB::table('tasks')
            ->whereNull('fecha_limite')
            ->orderBy('id')
            ->chunkById(200, function ($tareas) {
                foreach ($tareas as $tarea) {
                    $fechaInicio = $tarea->fecha_inicio
                        ?? Carbon::parse($tarea->fecha_asignacion ?? $tarea->created_at)->toDateString();

                    $fechaLimite = Carbon::parse($fechaInicio)->addDays(3)->setTime(17, 0);

                    DB::table('tasks')->where('id', $tarea->id)->update([
                        'fecha_limite' => $fechaLimite,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Backfill de datos historicos: no hay un estado "anterior" que
        // restaurar de forma segura (no se registra cuales filas eran NULL
        // antes de este backfill).
    }
};
