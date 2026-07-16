<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Politicas de SLA: definen las horas de resolucion esperadas
 * segun el tipo de trabajo y la prioridad. Se usan para calcular
 * automaticamente la fecha limite de cada tarea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['software', 'soporte', 'infraestructura']);
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica']);
            // Horas habiles/corridas objetivo para resolver
            $table->unsignedInteger('horas_resolucion');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['tipo', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
