<?php

namespace App\Domain\Organization\DTOs;

use App\Domain\Organization\Models\Role;

final readonly class RoleData
{
    public function __construct(
        public ?int $id,
        public string $nombre,
        public string $slug,
        public ?int $parentRoleId,
        public ?int $departmentId,
        public bool $isPrimary = false,
        public bool $isDeletable = true,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: $role->id,
            nombre: $role->nombre,
            slug: $role->slug,
            parentRoleId: $role->parent_role_id,
            departmentId: $role->department_id,
            isPrimary: $role->is_primary,
            isDeletable: $role->is_deletable,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'parent_role_id' => $this->parentRoleId,
            'department_id' => $this->departmentId,
            'is_primary' => $this->isPrimary,
            'is_deletable' => $this->isDeletable,
        ];
    }
}
