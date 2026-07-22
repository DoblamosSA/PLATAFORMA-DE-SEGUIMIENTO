<?php

namespace App\Domain\Organization\Repositories\Eloquent;

use App\Domain\Organization\DTOs\RoleData;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    public function findBySlug(string $slug): ?Role
    {
        return Role::where('slug', $slug)->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('nombre')->get();
    }

    public function create(RoleData $data): Role
    {
        return Role::create($data->toArray());
    }

    public function update(Role $role, RoleData $data): Role
    {
        $role->update($data->toArray());

        return $role->fresh();
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function ancestorsOf(Role $role): Collection
    {
        $chain = collect([$role]);
        $current = $role;
        $visited = [$role->id];

        while ($current->parent_role_id !== null) {
            $parent = $current->parent ?? Role::find($current->parent_role_id);

            if (! $parent || in_array($parent->id, $visited, true)) {
                break; // referencia rota o ciclo: cortar en vez de bucle infinito
            }

            $chain->prepend($parent);
            $visited[] = $parent->id;
            $current = $parent;
        }

        return $chain;
    }

    public function isDescendantOrSelf(Role $role, Role $candidateParent): bool
    {
        if ($role->id === $candidateParent->id) {
            return true;
        }

        $descendantIds = $this->collectDescendantIds($role);

        return in_array($candidateParent->id, $descendantIds, true);
    }

    /**
     * @return array<int, int>
     */
    private function collectDescendantIds(Role $role): array
    {
        $ids = [];
        $queue = $role->children()->pluck('id')->all();

        while ($queue) {
            $childId = array_shift($queue);

            if (in_array($childId, $ids, true)) {
                continue; // guard anti-ciclo
            }

            $ids[] = $childId;
            $queue = array_merge($queue, Role::where('parent_role_id', $childId)->pluck('id')->all());
        }

        return $ids;
    }

    public function writePermissionRow(Role $role, Permission $permission, string $tipo): void
    {
        $role->permissions()->syncWithoutDetaching([
            $permission->id => ['tipo' => $tipo],
        ]);
    }

    public function syncPermissions(Role $role, array $grantPermissionIds, array $denyPermissionIds): void
    {
        $sync = [];

        foreach ($grantPermissionIds as $id) {
            $sync[$id] = ['tipo' => 'grant'];
        }

        foreach ($denyPermissionIds as $id) {
            $sync[$id] = ['tipo' => 'deny'];
        }

        $role->permissions()->sync($sync);
    }

    public function isAssignedToAnyDepartment(Role $role): bool
    {
        return \Illuminate\Support\Facades\DB::table('department_user')->where('role_id', $role->id)->exists();
    }
}
