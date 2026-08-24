<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fecha_inicio y fecha_limite pasan a NOT NULL: ahora se ingresan siempre a
 * mano en el formulario de tareas (ver FormTarea::rules()), ya no se
 * auto-calculan. Requiere el backfill previo
 * (2026_08_24_100100_backfill_task_dates_before_required).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable(false)->change();
            $table->timestamp('fecha_limite')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->change();
            $table->timestamp('fecha_limite')->nullable()->change();
        });
    }
};
