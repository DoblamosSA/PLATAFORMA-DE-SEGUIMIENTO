<?php

namespace App\Domain\Organization\Repositories\Contracts;

use App\Domain\Organization\DTOs\RoleData;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function find(int $id): ?Role;

    public function findBySlug(string $slug): ?Role;

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    public function all(): \Illuminate\Database\Eloquent\Collection;

    public function create(RoleData $data): Role;

    public function update(Role $role, RoleData $data): Role;

    public function delete(Role $role): void;

    /**
     * Cadena de roles ancestros de raiz a hoja (el propio $role incluido al final).
     *
     * @return Collection<int, Role>
     */
    public function ancestorsOf(Role $role): Collection;

    /**
     * True si $candidateParent es el propio rol o uno de sus descendientes
     * (evita crear un ciclo de herencia).
     */
    public function isDescendantOrSelf(Role $role, Role $candidateParent): bool;

    public function writePermissionRow(Role $role, Permission $permission, string $tipo): void;

    /**
     * Reemplaza por completo las filas de role_permissions del rol: los
     * permisos que no aparezcan en ninguno de los dos arreglos quedan sin
     * fila (es decir, "sin override": heredan lo que diga el padre).
     *
     * @param  array<int, int>  $grantPermissionIds
     * @param  array<int, int>  $denyPermissionIds
     */
    public function syncPermissions(Role $role, array $grantPermissionIds, array $denyPermissionIds): void;

    public function isAssignedToAnyDepartment(Role $role): bool;
}
