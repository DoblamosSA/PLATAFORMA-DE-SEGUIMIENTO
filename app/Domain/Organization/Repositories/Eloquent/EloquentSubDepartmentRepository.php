<?php

namespace App\Domain\Organization\Repositories\Eloquent;

use App\Domain\Organization\DTOs\SubDepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Repositories\Contracts\SubDepartmentRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EloquentSubDepartmentRepository implements SubDepartmentRepositoryInterface
{
    public function find(int $id): ?SubDepartment
    {
        return SubDepartment::find($id);
    }

    public function allForDepartment(Department $department): Collection
    {
        return $department->subDepartments()->orderBy('nombre')->get();
    }

    public function create(SubDepartmentData $data): SubDepartment
    {
        return SubDepartment::create($data->toArray());
    }

    public function update(SubDepartment $subDepartment, SubDepartmentData $data): SubDepartment
    {
        $subDepartment->update($data->toArray());

        return $subDepartment->fresh();
    }

    public function delete(SubDepartment $subDepartment): void
    {
        $subDepartment->delete();
    }

    public function attachUser(SubDepartment $subDepartment, User $user): void
    {
        $subDepartment->users()->syncWithoutDetaching([$user->id]);
    }

    public function detachUser(SubDepartment $subDepartment, User $user): void
    {
        $subDepartment->users()->detach($user->id);
    }
}
