<?php

namespace App\Domain\Organization\Repositories\Eloquent;

use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    public function findBySlug(string $slug): ?Permission
    {
        return Permission::where('slug', $slug)->first();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::orderBy('grupo')->orderBy('slug')->get();
    }

    public function allGroupedByGrupo(): Collection
    {
        return $this->all()->groupBy('grupo');
    }
}
