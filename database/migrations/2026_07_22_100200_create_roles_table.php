<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles del sistema RBAC. Los roles primarios (is_primary=true) son
 * globales (department_id null), no eliminables, y sirven de raiz de
 * herencia para los roles heredados que un departamento puede crear
 * (parent_role_id + department_id). El slug es unico globalmente: los
 * roles heredados por departamento deben generar slugs con prefijo del
 * departamento para no colisionar con los 5 roles primarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->foreignId('parent_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_deletable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
