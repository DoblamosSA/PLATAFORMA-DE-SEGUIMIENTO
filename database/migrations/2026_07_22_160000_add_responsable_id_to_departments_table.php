<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsable del departamento (debe ser un Administrador). Nullable a nivel
 * de esquema para no romper departamentos existentes; el formulario lo exige
 * como obligatorio al crear/editar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('responsable_id')->nullable()->after('descripcion')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_id');
        });
    }
};
