<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Domain\Organization\Repositories\Contracts\SubDepartmentRepositoryInterface;
use App\Models\Project;
use App\Models\SlaPolicy;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $departments,
        private readonly SubDepartmentRepositoryInterface $subDepartments,
    ) {}

    public function create(DepartmentData $data): Department
    {
        return $this->departments->create($data);
    }

    public function update(Department $department, DepartmentData $data): Department
    {
        return $this->departments->update($department, $data);
    }

    /**
     * Elimina el departamento de forma permanente junto con sus subdepartamentos,
     * los roles propios del departamento, y sus colaboradores (con los proyectos
     * y tareas que estos tengan a su cargo). Los usuarios con rol superadmin nunca
     * se eliminan, solo se desvinculan del departamento.
     */
    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department) {
            $subDepartments = $this->subDepartments->allForDepartment($department);

            $userIds = $department->users()->pluck('users.id')
                ->merge($subDepartments->flatMap(fn (SubDepartment $sd) => $sd->users()->pluck('users.id')))
                ->unique();

            User::whereIn('id', $userIds)->get()
                ->reject(fn (User $u) => $u->esSuperAdmin())
                ->each(function (User $user) {
                    Project::where('responsable_id', $user->id)->delete();
                    Task::where('asignado_id', $user->id)->delete();
                    $user->delete();
                });

            Role::where('department_id', $department->id)->delete();

            // Cualquier proyecto/tarea que aun apunte a estos subdepartamentos
            // (aunque su responsable/asignado no sea de este departamento) debe
            // limpiarse antes: sub_department_id tiene restrictOnDelete.
            $subDepartments->each(function (SubDepartment $sd) {
                Project::where('sub_department_id', $sd->id)->delete();
                Task::where('sub_department_id', $sd->id)->delete();
                SlaPolicy::where('sub_department_id', $sd->id)->delete();
                $this->subDepartments->forceDelete($sd);
            });

            $this->departments->forceDelete($department);
        });
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
