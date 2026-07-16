<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipo de un proyecto: relacion muchos-a-muchos entre proyectos y
 * usuarios (desarrolladores). Un proyecto puede tener varios integrantes
 * y a cada uno solo se le pueden asignar tareas de ese proyecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Rol dentro del proyecto (desarrollador, qa, lider_tecnico, etc.)
            $table->string('rol_en_proyecto')->default('desarrollador');
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
