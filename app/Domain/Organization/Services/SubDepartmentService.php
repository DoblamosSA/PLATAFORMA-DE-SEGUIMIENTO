<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\DTOs\SubDepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Repositories\Contracts\SubDepartmentRepositoryInterface;
use App\Models\User;

class SubDepartmentService
{
    public function __construct(
        private readonly SubDepartmentRepositoryInterface $subDepartments,
    ) {}

    public function create(SubDepartmentData $data): SubDepartment
    {
        return $this->subDepartments->create($data);
    }

    public function update(SubDepartment $subDepartment, SubDepartmentData $data): SubDepartment
    {
        if ($subDepartment->department_id !== $data->departmentId) {
            throw new \InvalidArgumentException('No se puede mover un subdepartamento a otro departamento mediante update().');
        }

        return $this->subDepartments->update($subDepartment, $data);
    }

    public function delete(SubDepartment $subDepartment): void
    {
        $this->subDepartments->delete($subDepartment);
    }

    public function addUserToSubDepartment(User $user, SubDepartment $subDepartment): void
    {
        $this->subDepartments->attachUser($subDepartment, $user);
    }

    public function removeUserFromSubDepartment(User $user, SubDepartment $subDepartment): void
    {
        $this->subDepartments->detachUser($subDepartment, $user);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SubDepartment> */
    public function allForDepartment(Department $department): \Illuminate\Database\Eloquent\Collection
    {
        return $this->subDepartments->allForDepartment($department);
    }
}
