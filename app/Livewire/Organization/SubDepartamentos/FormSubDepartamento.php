<?php

namespace App\Livewire\Organization\SubDepartamentos;

use App\Domain\Organization\DTOs\SubDepartmentData;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Services\SubDepartmentService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormSubDepartamento extends Component
{
    public ?SubDepartment $subDepartment = null;

    public bool $enModal = false;

    public string $department_id = '';

    public string $nombre = '';

    public string $descripcion = '';

    public string $icono = 'sitemap';

    public string $color = 'slate';

    public bool $activo = true;

    public function mount(?SubDepartment $subDepartment = null, bool $enModal = false): void
    {
        $this->authorize($subDepartment?->exists ? 'update' : 'create', $subDepartment ?? SubDepartment::class);

        $this->enModal = $enModal;

        if ($subDepartment?->exists) {
            $this->subDepartment = $subDepartment;
            $this->department_id = (string) $subDepartment->department_id;
            $this->nombre = $subDepartment->nombre;
            $this->descripcion = $subDepartment->descripcion ?? '';
            $this->icono = $subDepartment->icono;
            $this->color = $subDepartment->color;
            $this->activo = $subDepartment->activo;
        }
    }

    protected function rules(): array
    {
        return [
            'department_id' => 'required|exists:departments,id',
            'nombre' => [
                'required', 'string', 'min:2', 'max:255',
                Rule::unique('sub_departments', 'nombre')
                    ->where('department_id', $this->department_id)
                    ->ignore($this->subDepartment?->id),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'icono' => 'required|in:'.implode(',', SubDepartment::ICONOS),
            'color' => 'required|in:'.implode(',', array_keys(SubDepartment::COLORES)),
            'activo' => 'boolean',
        ];
    }

    public function save(SubDepartmentService $service)
    {
        $data = $this->validate();

        $esNuevo = ! $this->subDepartment;
        $slug = $esNuevo || $this->subDepartment->department_id != $data['department_id']
            ? $this->generarSlugUnico((int) $data['department_id'], $data['nombre'])
            : $this->subDepartment->slug;

        $dto = new SubDepartmentData(
            id: $this->subDepartment?->id,
            departmentId: (int) $data['department_id'],
            nombre: $data['nombre'],
            slug: $slug,
            descripcion: $data['descripcion'] ?: null,
            icono: $data['icono'],
            color: $data['color'],
            activo: $data['activo'],
        );

        $esNuevo ? $service->create($dto) : $service->update($this->subDepartment, $dto);

        $mensaje = $esNuevo ? 'Subdepartamento creado correctamente.' : 'Subdepartamento actualizado.';

        if ($this->enModal) {
            // El padre dispara el toast (ver ListaSubDepartamentos::cerrarModal):
            // ver el comentario en cancelar() sobre por que se usa ->to() aqui.
            $this->dispatch('cerrar-modal-subdepartamento', mensaje: $mensaje)->to('organization.sub-departamentos.lista-sub-departamentos');

            return;
        }

        session()->flash('ok', $mensaje);
        $this->dispatch('app-toast', type: 'success', message: $mensaje);

        return $this->redirect(route('subdepartamentos'), navigate: true);
    }

    /**
     * ->to() en vez de dispatch() simple: este componente esta montado
     * dinamicamente dentro del modal del padre, y tras una accion Livewire
     * puede dejar su propio elemento en un estado que ya no propaga eventos
     * de forma confiable (bug de Livewire 3 con componentes anidados via @if).
     * ->to() ubica al padre por nombre y dispara el evento directo en su
     * elemento, sin depender del DOM de este hijo.
     */
    public function cancelar(): void
    {
        $this->dispatch('cerrar-modal-subdepartamento')->to('organization.sub-departamentos.lista-sub-departamentos');
    }

    private function generarSlugUnico(int $departmentId, string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $i = 1;

        while (SubDepartment::where('department_id', $departmentId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.organization.sub-departamentos.form-sub-departamento', [
            'departamentos' => Department::orderBy('nombre')->get(),
        ]);
    }
}
