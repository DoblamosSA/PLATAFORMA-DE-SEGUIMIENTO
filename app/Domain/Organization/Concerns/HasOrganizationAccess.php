<?php

namespace App\Domain\Organization\Concerns;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Services\PermissionResolutionService;
use App\Domain\Organization\Services\RoleContextService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Aporta al modelo User el acceso al sistema de departamentos/roles/permisos
 * (fundacion RBAC), sin modificar ninguno de sus metodos existentes.
 */
trait HasOrganizationAccess
{
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('role_id', 'es_principal')
            ->withTimestamps();
    }

    public function subDepartments(): BelongsToMany
    {
        return $this->belongsToMany(SubDepartment::class, 'sub_department_user')->withTimestamps();
    }

    /** Roles globales (no ligados a un departamento), ej. SuperAdmin. */
    public function rolesGlobales(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /** Rol que este usuario ocupa dentro de un departamento especifico, si pertenece a el. */
    public function departmentRoleFor(Department $department): ?Role
    {
        $pivot = $this->departments()->where('departments.id', $department->id)->first()?->pivot;

        if (! $pivot || ! $pivot->role_id) {
            return null;
        }

        return Role::find($pivot->role_id);
    }

    public function esSuperAdmin(): bool
    {
        $context = app(RoleContextService::class);

        if ($context->active($this) !== null) {
            return $context->activeIsSuperAdmin($this);
        }

        if (method_exists($this, 'esAdmin') && $this->esAdmin()) {
            return true;
        }

        return $this->rolesGlobales()->where('slug', 'super-admin')->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        $active = app(RoleContextService::class)->active($this);
        $scopeTo = $active && $active['role_id'] ? Role::find($active['role_id']) : null;

        return app(PermissionResolutionService::class)->userHasPermission($this, $permissionSlug, $scopeTo);
    }
}
