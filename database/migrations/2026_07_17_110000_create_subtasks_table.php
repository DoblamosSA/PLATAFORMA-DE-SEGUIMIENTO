<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Desglose de una tarea en subtareas con horas estimadas. La suma de las
 * horas de las subtareas de una tarea actualiza el campo horas_estimadas
 * de la tarea principal (ver Task::recalcularHoras()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('titulo');
            $table->decimal('horas', 6, 2);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtasks');
    }
};
