<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * inicio_planificado: fecha/hora de inicio laboral de la tarea.
 * Se rellena desde fecha_inicio (dia) + hora de jornada por defecto.
 * fecha_limite sigue siendo el fin planificado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('inicio_planificado')->nullable()->after('fecha_asignacion');
        });

        $hora = (int) (env('OPERATIVA_HORA_INICIO', 8));
        $minuto = (int) (env('OPERATIVA_MINUTO_INICIO', 0));

        // Backfill no destructivo: dia de fecha_inicio (o fecha_asignacion) a las HH:MM.
        $tareas = DB::table('tasks')->select('id', 'fecha_inicio', 'fecha_asignacion')->get();
        foreach ($tareas as $t) {
            $base = $t->fecha_inicio ?: ($t->fecha_asignacion ?: null);
            if (! $base) {
                continue;
            }
            $dt = \Illuminate\Support\Carbon::parse($base)
                ->setTime($hora, $minuto, 0)
                ->format('Y-m-d H:i:s');
            DB::table('tasks')->where('id', $t->id)->update(['inicio_planificado' => $dt]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('inicio_planificado');
        });
    }
};
