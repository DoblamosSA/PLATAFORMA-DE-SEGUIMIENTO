<?php

namespace App\Domain\Organization\Repositories\Contracts;

use App\Domain\Organization\DTOs\SubDepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SubDepartmentRepositoryInterface
{
    public function find(int $id): ?SubDepartment;

    /** @return Collection<int, SubDepartment> */
    public function allForDepartment(Department $department): Collection;

    public function create(SubDepartmentData $data): SubDepartment;

    public function update(SubDepartment $subDepartment, SubDepartmentData $data): SubDepartment;

    public function delete(SubDepartment $subDepartment): void;

    public function forceDelete(SubDepartment $subDepartment): void;

    public function attachUser(SubDepartment $subDepartment, User $user): void;

    public function detachUser(SubDepartment $subDepartment, User $user): void;
}
