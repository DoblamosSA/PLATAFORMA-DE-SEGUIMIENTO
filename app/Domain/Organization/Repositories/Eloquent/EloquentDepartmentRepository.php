<?php

namespace App\Domain\Organization\Repositories\Eloquent;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EloquentDepartmentRepository implements DepartmentRepositoryInterface
{
    public function find(int $id): ?Department
    {
        return Department::find($id);
    }

    public function findBySlug(string $slug): ?Department
    {
        return Department::where('slug', $slug)->first();
    }

    public function all(): Collection
    {
        return Department::orderBy('nombre')->get();
    }

    public function create(DepartmentData $data): Department
    {
        return Department::create($data->toArray());
    }

    public function update(Department $department, DepartmentData $data): Department
    {
        $department->update($data->toArray());

        return $department->fresh();
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }

    public function forceDelete(Department $department): void
    {
        $department->forceDelete();
    }

    public function attachUser(Department $department, User $user, ?Role $role, bool $esPrincipal = false): void
    {
        $department->users()->syncWithoutDetaching([
            $user->id => ['role_id' => $role?->id, 'es_principal' => $esPrincipal],
        ]);
    }

    public function detachUser(Department $department, User $user): void
    {
        $department->users()->detach($user->id);
    }
}
