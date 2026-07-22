<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\DTOs\SubDepartmentData;
use App\Domain\Organization\Exceptions\SubDepartmentNotDeletableException;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Repositories\Contracts\SubDepartmentRepositoryInterface;
use App\Models\SlaPolicy;
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

    /**
     * Elimina el subdepartamento de forma permanente. Al no ser un soft delete,
     * la FK cascadeOnDelete de sub_department_user desvincula automaticamente a
     * sus colaboradores (que conservan su cuenta y su vinculo con el departamento).
     * Si tiene proyectos o tareas asociadas (sub_department_id con restrictOnDelete),
     * no puede eliminarse hasta reasignarlos a otro subdepartamento.
     */
    public function delete(SubDepartment $subDepartment): void
    {
        if ($subDepartment->projects()->exists() || $subDepartment->tasks()->exists()) {
            throw new SubDepartmentNotDeletableException(
                "El subdepartamento '{$subDepartment->nombre}' tiene proyectos o tareas asociadas y no puede eliminarse."
            );
        }

        // Las politicas de SLA son configuracion propia del subdepartamento:
        // se eliminan con el, a diferencia de proyectos/tareas (trabajo real).
        SlaPolicy::where('sub_department_id', $subDepartment->id)->delete();

        $this->subDepartments->forceDelete($subDepartment);
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
