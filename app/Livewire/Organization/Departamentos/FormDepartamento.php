<?php

namespace App\Livewire\Organization\Departamentos;

use App\Domain\Organization\DTOs\DepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Services\DepartmentService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormDepartamento extends Component
{
    public ?Department $department = null;

    public string $nombre = '';

    public string $descripcion = '';

    public bool $activo = true;

    public function mount(?Department $department = null): void
    {
        $this->authorize($department?->exists ? 'update' : 'create', $department ?? Department::class);

        if ($department?->exists) {
            $this->department = $department;
            $this->nombre = $department->nombre;
            $this->descripcion = $department->descripcion ?? '';
            $this->activo = $department->activo;
        }
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:255', Rule::unique('departments', 'nombre')->ignore($this->department?->id)],
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ];
    }

    public function save(DepartmentService $service)
    {
        $data = $this->validate();

        $esNuevo = ! $this->department;
        $slug = $esNuevo ? $this->generarSlugUnico($data['nombre']) : $this->department->slug;

        $dto = new DepartmentData(
            id: $this->department?->id,
            nombre: $data['nombre'],
            slug: $slug,
            descripcion: $data['descripcion'] ?: null,
            activo: $data['activo'],
        );

        $esNuevo ? $service->create($dto) : $service->update($this->department, $dto);

        session()->flash('ok', $esNuevo ? 'Departamento creado correctamente.' : 'Departamento actualizado.');

        return $this->redirect(route('departamentos'), navigate: true);
    }

    private function generarSlugUnico(string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $i = 1;

        while (Department::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.organization.departamentos.form-departamento');
    }
}
