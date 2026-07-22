<?php

namespace App\Domain\Organization\DTOs;

use App\Domain\Organization\Models\Department;

final readonly class DepartmentData
{
    public function __construct(
        public ?int $id,
        public string $nombre,
        public string $slug,
        public ?string $descripcion,
        public bool $activo = true,
    ) {}

    public static function fromModel(Department $department): self
    {
        return new self(
            id: $department->id,
            nombre: $department->nombre,
            slug: $department->slug,
            descripcion: $department->descripcion,
            activo: $department->activo,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }
}
