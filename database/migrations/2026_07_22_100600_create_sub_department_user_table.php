<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membresia de un usuario en un subdepartamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_department_id')->constrained('sub_departments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sub_department_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_department_user');
    }
};
