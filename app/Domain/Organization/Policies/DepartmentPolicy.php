<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Organization\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermission('departments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('departments.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasPermission('departments.edit');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermission('departments.delete');
    }
}
