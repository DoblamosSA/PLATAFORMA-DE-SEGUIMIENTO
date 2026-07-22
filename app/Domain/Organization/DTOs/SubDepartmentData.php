<?php

namespace App\Domain\Organization\DTOs;

use App\Domain\Organization\Models\SubDepartment;

final readonly class SubDepartmentData
{
    public function __construct(
        public ?int $id,
        public int $departmentId,
        public string $nombre,
        public string $slug,
        public ?string $descripcion,
        public bool $activo = true,
    ) {}

    public static function fromModel(SubDepartment $subDepartment): self
    {
        return new self(
            id: $subDepartment->id,
            departmentId: $subDepartment->department_id,
            nombre: $subDepartment->nombre,
            slug: $subDepartment->slug,
            descripcion: $subDepartment->descripcion,
            activo: $subDepartment->activo,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'department_id' => $this->departmentId,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }
}
