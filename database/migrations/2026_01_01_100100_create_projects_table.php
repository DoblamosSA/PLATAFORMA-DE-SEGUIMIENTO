<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proyectos de tecnologia (software, soporte, infraestructura).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['software', 'soporte', 'infraestructura']);
            $table->enum('estado', ['planeado', 'en_progreso', 'en_pausa', 'completado', 'cancelado'])
                  ->default('planeado');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            // Lider responsable del proyecto
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->date('fecha_fin_real')->nullable();
            // Progreso 0-100 (se recalcula a partir de las tareas)
            $table->unsignedTinyInteger('progreso')->default(0);
            $table->timestamps();

            $table->index(['tipo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
