<?php

namespace App\Domain\Organization\Repositories\Contracts;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function find(int $id): ?Department;

    public function findBySlug(string $slug): ?Department;

    /** @return Collection<int, Department> */
    public function all(): Collection;

    public function create(DepartmentData $data): Department;

    public function update(Department $department, DepartmentData $data): Department;

    public function delete(Department $department): void;

    public function attachUser(Department $department, User $user, ?Role $role, bool $esPrincipal = false): void;

    public function detachUser(Department $department, User $user): void;
}
