<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil operativo del colaborador: contacto, foto, rol de evaluador y
 * disponibilidad semanal (dias laborales + horas diarias), usada para
 * calcular la capacidad y la carga de trabajo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable()->after('cargo');
            $table->string('foto_path')->nullable()->after('telefono');
            // Dias laborales: subconjunto de [L, M, X, J, V, S, D]
            $table->json('dias_laborales')->nullable()->after('foto_path');
            $table->decimal('horas_diarias', 4, 2)->nullable()->after('dias_laborales');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'lider', 'tecnico', 'evaluador'])->default('tecnico')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'foto_path', 'dias_laborales', 'horas_diarias']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'lider', 'tecnico'])->default('tecnico')->change();
        });
    }
};
