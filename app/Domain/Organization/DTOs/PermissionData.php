<?php

namespace App\Domain\Organization\DTOs;

use App\Domain\Organization\Models\Permission;

final readonly class PermissionData
{
    public function __construct(
        public ?int $id,
        public string $slug,
        public string $nombre,
        public ?string $descripcion,
        public ?string $grupo,
    ) {}

    public static function fromModel(Permission $permission): self
    {
        return new self(
            id: $permission->id,
            slug: $permission->slug,
            nombre: $permission->nombre,
            descripcion: $permission->descripcion,
            grupo: $permission->grupo,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'grupo' => $this->grupo,
        ];
    }
}
