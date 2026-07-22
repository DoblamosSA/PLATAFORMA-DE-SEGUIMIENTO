<?php

namespace App\Providers;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Policies\DepartmentPolicy;
use App\Domain\Organization\Policies\SubDepartmentPolicy;
use App\Domain\Organization\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Domain\Organization\Repositories\Contracts\PermissionRepositoryInterface;
use App\Domain\Organization\Repositories\Contracts\RoleRepositoryInterface;
use App\Domain\Organization\Repositories\Contracts\SubDepartmentRepositoryInterface;
use App\Domain\Organization\Repositories\Eloquent\EloquentDepartmentRepository;
use App\Domain\Organization\Repositories\Eloquent\EloquentPermissionRepository;
use App\Domain\Organization\Repositories\Eloquent\EloquentRoleRepository;
use App\Domain\Organization\Repositories\Eloquent\EloquentSubDepartmentRepository;
use App\Domain\Organization\Services\RoleContextService;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Fundacion RBAC (Departamentos/SubDepartamentos/Roles/Permisos). Aditivo:
 * no reemplaza ni modifica el Gate::define('admin', ...) existente en
 * AppServiceProvider ni el grupo de rutas 'can:admin'.
 */
class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DepartmentRepositoryInterface::class, EloquentDepartmentRepository::class);
        $this->app->bind(SubDepartmentRepositoryInterface::class, EloquentSubDepartmentRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, EloquentPermissionRepository::class);

        // Singleton: sus metodos se llaman repetidas veces por request (nav,
        // gates, es*() de User) y memoiza candidatos/contexto internamente.
        $this->app->singleton(RoleContextService::class);
    }

    public function boot(): void
    {
        Gate::before(fn (User $user, string $ability) => $user->esSuperAdmin() ? true : null);

        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(SubDepartment::class, SubDepartmentPolicy::class);

        Gate::define('departments.manage', fn (User $user) => $user->hasPermission('departments.manage'));
        Gate::define('roles.manage', fn (User $user) => $user->hasPermission('roles.manage'));
        Gate::define('users.manage', fn (User $user) => $user->hasPermission('users.manage'));
    }
}
