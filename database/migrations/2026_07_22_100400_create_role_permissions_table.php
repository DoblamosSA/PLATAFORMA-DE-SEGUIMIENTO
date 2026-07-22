<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permisos explicitos de un rol. 'grant' otorga el permiso, 'deny' lo
 * revoca explicitamente (usado por roles heredados para quitar un permiso
 * que su rol padre concede). PermissionResolutionService recorre la
 * cadena de roles padres (raiz -> hoja) aplicando estas filas en orden,
 * de forma que el 'deny' de un rol hijo siempre gana sobre el 'grant' de
 * un ancestro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->enum('tipo', ['grant', 'deny'])->default('grant');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
