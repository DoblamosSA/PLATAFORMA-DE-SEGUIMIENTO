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
        return $user->hasPermission('subdepartments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('subdepartments.create');
    }

    public function update(User $user, SubDepartment $subDepartment): bool
    {
        return $user->hasPermission('subdepartments.edit');
    }

    public function delete(User $user, SubDepartment $subDepartment): bool
    {
        return $user->hasPermission('subdepartments.delete');
    }
}
