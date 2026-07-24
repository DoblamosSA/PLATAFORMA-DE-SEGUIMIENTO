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
            $this->authorize('create', Department::class);
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        } elseif ($department?->exists) {
            $this->authorize('update', $department);
            $this->mostrarModal = true;
            $this->editando = $department;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        $this->authorize('create', Department::class);

        $this->editando = null;
        $this->mostrarModal = true;
    }

    public function abrirEditar(int $departmentId): void
    {
        $department = Department::findOrFail($departmentId);
        $this->authorize('update', $department);

        $this->editando = $department;
        $this->mostrarModal = true;
    }

    /**
     * El toast se dispara aqui (no en FormDepartamento) porque este
     * componente es quien recibe el evento via ->to() del hijo: su propio
     * elemento es estable, mientras que el del hijo puede haber quedado en
     * un estado que Livewire ya no propaga de forma confiable justo
     * despues de la accion de guardar.
     */
    #[On('cerrar-modal-departamento')]
    public function cerrarModal(?string $mensaje = null): void
    {
        $this->mostrarModal = false;
        $this->editando = null;

        if ($mensaje) {
            $this->dispatch('app-toast', type: 'success', message: $mensaje);
        }

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
        $this->dispatch('app-toast', type: 'success', message: 'Departamento eliminado.');
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
