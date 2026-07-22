<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\DTOs\RoleData;
use App\Domain\Organization\Exceptions\RoleHierarchyException;
use App\Domain\Organization\Exceptions\RoleNotDeletableException;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\PermissionRepositoryInterface;
use App\Domain\Organization\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    /**
     * Crea un rol heredado, escribiendo sus filas de permisos agregados
     * (grant) y quitados (deny) respecto al rol padre.
     *
     * @param  array<int, string>  $grantedSlugs  slugs de permisos que este rol agrega
     * @param  array<int, string>  $revokedSlugs  slugs de permisos que este rol quita del heredado
     */
    public function createInheritedRole(RoleData $data, array $grantedSlugs = [], array $revokedSlugs = []): Role
    {
        if ($data->parentRoleId !== null) {
            $parent = $this->roles->find($data->parentRoleId);

            if (! $parent) {
                throw new RoleHierarchyException("El rol padre #{$data->parentRoleId} no existe.");
            }
        }

        return DB::transaction(function () use ($data, $grantedSlugs, $revokedSlugs) {
            $role = $this->roles->create($data);

            foreach ($grantedSlugs as $slug) {
                $this->applyPermission($role, $slug, 'grant');
            }

            foreach ($revokedSlugs as $slug) {
                $this->applyPermission($role, $slug, 'deny');
            }

            return $role;
        });
    }

    public function updateParent(Role $role, ?Role $newParent): Role
    {
        if ($newParent && $this->roles->isDescendantOrSelf($role, $newParent)) {
            throw new RoleHierarchyException('No se puede asignar como padre a un descendiente (o al propio rol): se crearia un ciclo de herencia.');
        }

        $role->update(['parent_role_id' => $newParent?->id]);

        return $role->fresh();
    }

    public function deleteRole(Role $role): void
    {
        if (! $role->is_deletable) {
            throw new RoleNotDeletableException("El rol '{$role->nombre}' es primario y no puede eliminarse.");
        }

        if ($this->roles->isAssignedToAnyDepartment($role)) {
            throw new RoleNotDeletableException("El rol '{$role->nombre}' esta asignado a usuarios de un departamento y no puede eliminarse.");
        }

        $this->roles->delete($role);
    }

    /**
     * Roles que pueden elegirse como padre en el formulario: todos menos el
     * propio rol y sus descendientes (evita crear un ciclo de herencia).
     * Si $role es null (creacion de un rol nuevo) no hay nada que excluir.
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    public function assignableParentsFor(?Role $role): \Illuminate\Support\Collection
    {
        $todos = collect($this->roles->all());

        if (! $role) {
            return $todos;
        }

        return $todos->reject(fn (Role $candidato) => $this->roles->isDescendantOrSelf($role, $candidato));
    }

    /**
     * Reemplaza el conjunto de permisos agregados/quitados de un rol
     * heredado ya existente (usado al editarlo).
     *
     * @param  array<int, string>  $grantedSlugs
     * @param  array<int, string>  $revokedSlugs
     */
    public function updatePermissions(Role $role, array $grantedSlugs = [], array $revokedSlugs = []): void
    {
        $grantIds = $this->slugsToIds($grantedSlugs);
        $denyIds = $this->slugsToIds($revokedSlugs);

        $this->roles->syncPermissions($role, $grantIds, $denyIds);
    }

    /**
     * Duplica un rol heredado: mismo padre/departamento, mismos permisos
     * agregados/quitados, nunca primario ni protegido de eliminacion.
     */
    public function duplicateRole(Role $role): Role
    {
        return DB::transaction(function () use ($role) {
            $copia = $this->roles->create(new RoleData(
                id: null,
                nombre: $role->nombre.' (copia)',
                slug: $role->slug.'-copia-'.substr(bin2hex(random_bytes(4)), 0, 6),
                parentRoleId: $role->parent_role_id,
                departmentId: $role->department_id,
                isPrimary: false,
                isDeletable: true,
            ));

            $grantIds = $role->grantedPermissions()->pluck('permissions.id')->all();
            $denyIds = $role->deniedPermissions()->pluck('permissions.id')->all();

            $this->roles->syncPermissions($copia, $grantIds, $denyIds);

            return $copia;
        });
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<int, int>
     */
    private function slugsToIds(array $slugs): array
    {
        return collect($slugs)
            ->map(fn (string $slug) => $this->permissions->findBySlug($slug)?->id)
            ->filter()
            ->values()
            ->all();
    }

    private function applyPermission(Role $role, string $permissionSlug, string $tipo): void
    {
        $permission = $this->permissions->findBySlug($permissionSlug);

        if (! $permission) {
            throw new \InvalidArgumentException("Permiso desconocido: {$permissionSlug}");
        }

        $this->roles->writePermissionRow($role, $permission, $tipo);
    }
}
