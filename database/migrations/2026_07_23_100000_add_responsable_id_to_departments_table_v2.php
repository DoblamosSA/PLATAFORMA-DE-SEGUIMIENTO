<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reintento de la migracion 2026_07_22_160000: en produccion quedo
 * registrada en la tabla `migrations` pero la columna nunca se creo
 * (archivo ausente del contenedor desplegado). Se agrega con guard para
 * ser inofensiva si esa migracion original llegara a aplicarse despues.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('departments', 'responsable_id')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('responsable_id')->nullable()->after('descripcion')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('departments', 'responsable_id')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_id');
        });
    }
};
