<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Organization\Models\SubDepartment;
use App\Models\User;

class SubDepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subdepartments.view');
    }

    public function view(User $user, SubDepartment $subDepartment): bool
    {
        return $user->hasPermission('subdepartments.view') && $this->mismoDepartamento($user, $subDepartment);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('subdepartments.create');
    }

    public function update(User $user, SubDepartment $subDepartment): bool
    {
        return $user->hasPermission('subdepartments.edit') && $this->mismoDepartamento($user, $subDepartment);
    }

    public function delete(User $user, SubDepartment $subDepartment): bool
    {
        return $user->hasPermission('subdepartments.delete') && $this->mismoDepartamento($user, $subDepartment);
    }

    /** Los departamentos son independientes entre si: salvo SuperAdmin, solo se gestiona el subdepartamento del propio departamento. */
    private function mismoDepartamento(User $user, SubDepartment $subDepartment): bool
    {
        return $user->esSuperAdmin() || $subDepartment->department_id === $user->departments()->first()?->id;
    }
}
