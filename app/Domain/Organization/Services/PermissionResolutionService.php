<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\DTOs\EffectivePermissionSetData;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\RoleRepositoryInterface;
use App\Models\User;

/**
 * Resuelve el conjunto efectivo de permisos de un rol recorriendo su
 * cadena de herencia (raiz -> hoja) y aplicando cada nivel de
 * grant/deny en orden, de forma que el override mas especifico
 * (el rol mas cercano a la hoja) siempre gana.
 */
class PermissionResolutionService
{
    /** @var array<int, EffectivePermissionSetData> */
    private array $cache = [];

    public function __construct(
        private readonly RoleRepositoryInterface $roles,
    ) {}

    public function resolveEffectivePermissions(Role $role): EffectivePermissionSetData
    {
        if (isset($this->cache[$role->id])) {
            return $this->cache[$role->id];
        }

        $chain = $this->roles->ancestorsOf($role); // raiz primero, $role al final

        $slugs = [];

        foreach ($chain as $levelRole) {
            foreach ($levelRole->permissions as $permission) {
                if ($permission->pivot->tipo === 'deny') {
                    unset($slugs[$permission->slug]);
                } else {
                    $slugs[$permission->slug] = true;
                }
            }
        }

        return $this->cache[$role->id] = new EffectivePermissionSetData($role->id, array_keys($slugs));
    }

    /**
     * Cuando $scopeTo se indica, el chequeo se limita a ese rol (uso: el
     * usuario eligio un contexto de rol activo tras el login); si no, se
     * evalua contra la union de todos los roles del usuario, como siempre.
     */
    public function userHasPermission(User $user, string $permissionSlug, ?Role $scopeTo = null): bool
    {
        $roles = $scopeTo ? collect([$scopeTo]) : $this->rolesOf($user);

        foreach ($roles as $role) {
            if ($this->resolveEffectivePermissions($role)->has($permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function rolesOf(User $user): \Illuminate\Support\Collection
    {
        $globales = $user->rolesGlobales()->get();
        $porDepartamento = Role::whereIn(
            'id',
            \Illuminate\Support\Facades\DB::table('department_user')
                ->where('user_id', $user->id)
                ->whereNotNull('role_id')
                ->pluck('role_id'),
        )->get();

        return $globales->concat($porDepartamento)->unique('id');
    }
}
