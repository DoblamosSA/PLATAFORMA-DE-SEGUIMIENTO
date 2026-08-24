<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quita el subsistema de SLA por completo: ya no se usa para calcular nada
 * (fecha_limite ahora se ingresa manualmente, ver
 * 2026_08_24_100200_make_task_dates_required_on_tasks_table). sla_policies
 * solo se creaba en el seeder y sla_horas solo se ponia en null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sla_policies');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('sla_horas');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('sla_horas')->nullable()->after('fecha_completada');
        });

        // Recrea la forma final de sla_policies (con sub_department_id, no el
        // "tipo" original) para que un rollback deje el esquema equivalente al
        // que existia justo antes de esta migracion.
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_department_id')->constrained('sub_departments')->restrictOnDelete();
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica']);
            $table->unsignedInteger('horas_resolucion');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['sub_department_id', 'prioridad']);
        });
    }
};
