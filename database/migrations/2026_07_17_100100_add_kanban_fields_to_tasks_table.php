<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicacion de cada tarea dentro del tablero Kanban de su proyecto:
 * columna a la que pertenece y su orden dentro de esa columna. El estado
 * canonico de la tarea se mantiene aparte (columna -> estado al mover).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('board_column_id')
                ->nullable()
                ->after('estado')
                ->constrained('board_columns')
                ->nullOnDelete();
            // Orden dentro de la columna
            $table->unsignedInteger('posicion')->default(0)->after('board_column_id');

            $table->index('board_column_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('board_column_id');
            $table->dropColumn('posicion');
        });
    }
};
