<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitacora / historial de cada tarea: cambios de estado, reasignaciones
 * y comentarios. Sirve de trazabilidad para auditar el cumplimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Tipo de evento: creacion, cambio_estado, reasignacion, comentario
            $table->string('accion');
            $table->text('detalle')->nullable();
            $table->timestamps();

            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
