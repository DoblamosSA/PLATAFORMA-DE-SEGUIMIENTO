<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas del tablero Kanban por proyecto. Son personalizables (nombre,
 * orden, alta/baja) pero cada una mapea a un estado canonico de tarea
 * ('pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada')
 * para no romper la logica de SLA/metricas: al mover una card a una columna,
 * la tarea toma el estado asociado a esa columna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('nombre');
            // Estado del sistema al que mapea la columna
            $table->string('estado')->default('pendiente');
            // Orden de la columna dentro del tablero
            $table->unsignedInteger('posicion')->default(0);
            // Token de color de la paleta existente (slate, sky, amber, emerald, ...)
            $table->string('color')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'posicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_columns');
    }
};
