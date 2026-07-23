<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\RoleRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Cuando un usuario tiene mas de una identidad de rol simultanea (el rol
 * legado en users.rol, un rol global via user_roles, y/o un rol por
 * departamento via department_user.role_id), esta clase resuelve las
 * opciones disponibles y mantiene en sesion cual esta activa.
 */
class RoleContextService
{
    private const SESSION_KEY = 'active_role_context';

    /** @var array<int, array<int, array{key: string, label: string, type: string, role_id: ?int, department_id: ?int}>> */
    private array $candidatesCache = [];

    public function __construct(
        private readonly RoleRepositoryInterface $roles,
    ) {}

    /** @return array<int, array{key: string, label: string, type: string, role_id: ?int, department_id: ?int}> */
    public function candidates(User $user): array
    {
        if (isset($this->candidatesCache[$user->id])) {
            return $this->candidatesCache[$user->id];
        }

        $candidates = [];

        if ($user->rol) {
            $candidates['legacy'] = [
                'key' => 'legacy',
                'label' => $user->rolLabel(),
                'type' => 'legacy',
                'role_id' => null,
                'department_id' => null,
            ];
        }

        foreach ($user->rolesGlobales()->get() as $role) {
            // Super Administrador no es una identidad distinta cuando el usuario
            // ya es Administrador por el campo legado: admin ya implica
            // super-admin (ver esSuperAdmin()), asi que no se ofrece como
            // opcion separada para elegir (evita el selector para todo admin).
            if ($role->slug === 'super-admin' && $user->rol === 'admin') {
                continue;
            }

            $key = "global:{$role->id}";
            $candidates[$key] = [
                'key' => $key,
                'label' => $role->nombre,
                'type' => 'global',
                'role_id' => $role->id,
                'department_id' => null,
            ];
        }

        foreach ($user->departments()->get() as $department) {
            $roleId = $department->pivot->role_id;

            if (! $roleId) {
                continue;
            }

            $key = "department:{$department->id}:{$roleId}";
            $candidates[$key] = [
                'key' => $key,
                'label' => (Role::find($roleId)?->nombre ?? 'Rol').' — '.$department->nombre,
                'type' => 'department',
                'role_id' => $roleId,
                'department_id' => $department->id,
            ];
        }

        return $this->candidatesCache[$user->id] = array_values($candidates);
    }

    public function hasChoice(User $user): bool
    {
        return count($this->candidates($user)) > 1;
    }

    public function activate(User $user, string $key): void
    {
        if (! collect($this->candidates($user))->firstWhere('key', $key)) {
            return;
        }

        Session::put(self::SESSION_KEY, $key);
    }

    /**
     * El contexto activo solo aplica al usuario actualmente autenticado: esta
     * elección vive en su sesión de login, no es un atributo del modelo. Si
     * $user es otro usuario (ej. un colaborador listado por un admin, o el
     * asignado de una tarea), esto siempre retorna null.
     *
     * @return array{key: string, label: string, type: string, role_id: ?int, department_id: ?int}|null
     */
    public function active(User $user): ?array
    {
        if (! Auth::check() || Auth::id() !== $user->id) {
            return null;
        }

        $key = Session::get(self::SESSION_KEY);

        if (! $key) {
            return null;
        }

        return collect($this->candidates($user))->firstWhere('key', $key);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /** Slug del rol legado (admin/lider/tecnico/evaluador) que gobierna esAdmin()/esCoordinador()/etc. */
    public function effectiveLegacyRol(User $user): string
    {
        $active = $this->active($user);

        if (! $active || $active['type'] === 'legacy') {
            return (string) $user->rol;
        }

        $root = $this->rootSlugFor($active);

        // Super Administrador no tiene contraparte legada: implica admin.
        return match ($root) {
            null => (string) $user->rol,
            'super-admin' => 'admin',
            default => $root,
        };
    }

    public function activeIsSuperAdmin(User $user): bool
    {
        $active = $this->active($user);

        return $active !== null && $this->rootSlugFor($active) === 'super-admin';
    }

    /** Rol raiz (uno de los 5 roles primarios) al que pertenece el contexto activo, via su cadena de herencia. */
    private function rootSlugFor(array $context): ?string
    {
        if (! $context['role_id']) {
            return null;
        }

        $role = Role::find($context['role_id']);

        if (! $role) {
            return null;
        }

        return $this->roles->ancestorsOf($role)->first()?->slug ?? $role->slug;
    }
}
