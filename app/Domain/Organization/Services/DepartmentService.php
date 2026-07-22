<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Models\User;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $departments,
    ) {}

    public function create(DepartmentData $data): Department
    {
        return $this->departments->create($data);
    }

    public function update(Department $department, DepartmentData $data): Department
    {
        return $this->departments->update($department, $data);
    }

    public function delete(Department $department): void
    {
        $this->departments->delete($department);
    }

    public function assignUserToDepartment(User $user, Department $department, ?Role $role = null, bool $esPrincipal = false): void
    {
        $this->departments->attachUser($department, $user, $role, $esPrincipal);
    }

    public function removeUserFromDepartment(User $user, Department $department): void
    {
        $this->departments->detachUser($department, $user);
    }
}
