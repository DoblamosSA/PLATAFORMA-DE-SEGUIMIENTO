<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha de inicio planificada (para distribuir horas estimadas entre dias
 * laborables del colaborador), etiqueta obligatoria de certificacion para
 * tareas creadas por un evaluador, y el estado "rechazada" que usa el
 * evaluador al rechazar una tarea completada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('fecha_asignacion');
            $table->string('tag', 40)->nullable()->after('tipo');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada', 'rechazada'])
                ->default('pendiente')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada'])
                ->default('pendiente')
                ->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'tag']);
        });
    }
};
