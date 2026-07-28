<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_activity_id')->nullable()->constrained('task_activities')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('nombre_original');
            $table->string('mime', 100);
            $table->unsignedInteger('bytes');
            $table->timestamps();

            $table->index(['task_id', 'task_activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_evidences');
    }
};
