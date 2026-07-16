<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tareas / actividades asignables. Pueden pertenecer a un proyecto
 * o ser actividades sueltas (tipico en soporte). El cumplimiento SLA
 * se mide comparando fecha_completada contra fecha_limite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['software', 'soporte', 'infraestructura']);
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->enum('estado', ['pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada'])
                  ->default('pendiente');

            // Personas
            $table->foreignId('asignado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            // Fechas de control SLA
            $table->timestamp('fecha_asignacion')->nullable();  // cuando se asigno a la persona
            $table->timestamp('fecha_limite')->nullable();      // vencimiento segun SLA
            $table->timestamp('fecha_inicio_real')->nullable(); // cuando paso a en_progreso
            $table->timestamp('fecha_completada')->nullable();  // cuando paso a completada

            // Snapshot de horas SLA aplicadas al crear la tarea
            $table->unsignedInteger('sla_horas')->nullable();
            // true=a tiempo, false=vencida, null=aun sin cerrar
            $table->boolean('cumplida_a_tiempo')->nullable();

            $table->timestamps();

            $table->index(['estado', 'tipo']);
            $table->index('asignado_id');
            $table->index('fecha_limite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
