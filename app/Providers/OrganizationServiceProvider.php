<?php

namespace App\Providers;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Permission;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Fundacion RBAC (Departamentos/SubDepartamentos/Roles/Permisos/Colaboradores/
 * Proyectos/Tareas). El bypass universal es unicamente esSuperAdmin() (via
 * Gate::before): el enum legado 'admin'/esAdmin() ya NO otorga acceso libre
 * por si solo en ningun punto de autorizacion de este proveedor - todo pasa
 * por el permiso granular efectivo del rol (primario o heredado) del usuario.
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

        // Registra un Gate por cada permiso del catalogo (departments.create,
        // roles.edit, tasks.delete, users.manage, etc.) para poder usar
        // Gate::allows('<slug>') / @can('<slug>') en cualquier vista o accion
        // sin declarar cada uno a mano. Protegido con hasTable() para no
        // romper `php artisan migrate` en una instalacion nueva, antes de que
        // exista la tabla de permisos.
        if (Schema::hasTable('permissions')) {
            foreach (Permission::pluck('slug') as $slug) {
                Gate::define($slug, fn (User $user) => $user->hasPermission($slug));
            }
        }

        // El grupo de rutas de Tareas usa 'can:tasks.access' (no 'can:admin').
        Gate::define('tasks.access', fn (User $user) => $user->hasPermission('tasks.view'));
    }
}
