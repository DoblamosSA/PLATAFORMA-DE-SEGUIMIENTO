<?php

namespace App\Domain\Organization\Repositories\Contracts;

use App\Domain\Organization\Models\Permission;
use Illuminate\Support\Collection;

interface PermissionRepositoryInterface
{
    public function find(int $id): ?Permission;

    public function findBySlug(string $slug): ?Permission;

    /** @return \Illuminate\Database\Eloquent\Collection<int, Permission> */
    public function all(): \Illuminate\Database\Eloquent\Collection;

    /** @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, Permission>> */
    public function allGroupedByGrupo(): Collection;
}
