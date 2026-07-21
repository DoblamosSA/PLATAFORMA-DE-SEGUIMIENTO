<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horas estimadas de la tarea, calculadas como la suma de las horas de sus
 * subtareas (independiente de sla_horas, que es el objetivo de resolucion
 * segun la politica de SLA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('horas_estimadas', 6, 2)->nullable()->after('sla_horas');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('horas_estimadas');
        });
    }
};
