<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitacora general de trazabilidad para eventos que no pertenecen a una
 * tarea (alta/edicion de colaboradores, bloqueos de capacidad sin tarea
 * aun creada, etc). Complementa a task_activities.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->string('entidad_type')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->text('detalle')->nullable();
            $table->timestamps();

            $table->index(['entidad_type', 'entidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
