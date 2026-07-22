<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Icono y color de acento para el subdepartamento, usados en el dashboard
 * de cumplimiento y en los badges de tareas/proyectos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->string('icono')->default('sitemap')->after('descripcion');
            $table->string('color')->default('slate')->after('icono');
        });
    }

    public function down(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->dropColumn(['icono', 'color']);
        });
    }
};
