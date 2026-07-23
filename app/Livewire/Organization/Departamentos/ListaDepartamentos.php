<?php

namespace App\Livewire\Organization\Departamentos;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Services\DepartmentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaDepartamentos extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    public bool $mostrarModal = false;

    public ?Department $editando = null;

    /** True si el modal se abrio por una URL directa (departamentos.crear/editar): al cerrar hay que volver a /departamentos. */
    public bool $llegoPorRutaDirecta = false;

    public function mount(?Department $department = null): void
    {
        $this->authorize('viewAny', Department::class);

        if (request()->routeIs('departamentos.crear')) {
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        } elseif ($department?->exists) {
            $this->mostrarModal = true;
            $this->editando = $department;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        $this->editando = null;
        $this->mostrarModal = true;
    }

    public function abrirEditar(int $departmentId): void
    {
        $this->editando = Department::findOrFail($departmentId);
        $this->mostrarModal = true;
    }

    #[On('cerrar-modal-departamento')]
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->editando = null;

        if ($this->llegoPorRutaDirecta) {
            $this->llegoPorRutaDirecta = false;
            $this->redirect(route('departamentos'), navigate: true);
        }
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function eliminar(int $departmentId): void
    {
        $department = Department::findOrFail($departmentId);
        $this->authorize('delete', $department);

        app(DepartmentService::class)->delete($department);

        session()->flash('ok', 'Departamento eliminado.');
    }

    public function render()
    {
        $departamentos = Department::query()
            ->with('responsable')
            ->withCount(['subDepartments', 'users'])
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.organization.departamentos.lista-departamentos', [
            'departamentos' => $departamentos,
        ]);
    }
}
