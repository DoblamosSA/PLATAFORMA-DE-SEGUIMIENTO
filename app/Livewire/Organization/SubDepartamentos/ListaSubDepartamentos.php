<?php

namespace App\Livewire\Organization\SubDepartamentos;

use App\Domain\Organization\Exceptions\SubDepartmentNotDeletableException;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Services\SubDepartmentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaSubDepartamentos extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    #[Url]
    public string $departamento = '';

    public bool $mostrarModal = false;

    public ?SubDepartment $editando = null;

    /** True si el modal se abrio por una URL directa (subdepartamentos.crear/editar): al cerrar hay que volver a /subdepartamentos. */
    public bool $llegoPorRutaDirecta = false;

    public function mount(?SubDepartment $subDepartment = null): void
    {
        $this->authorize('viewAny', SubDepartment::class);

        if (request()->routeIs('subdepartamentos.crear')) {
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        } elseif ($subDepartment?->exists) {
            $this->mostrarModal = true;
            $this->editando = $subDepartment;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        $this->editando = null;
        $this->mostrarModal = true;
    }

    public function abrirEditar(int $subDepartmentId): void
    {
        $this->editando = SubDepartment::findOrFail($subDepartmentId);
        $this->mostrarModal = true;
    }

    /** El toast se dispara aqui (no en FormSubDepartamento): ver el comentario en FormSubDepartamento::cancelar(). */
    #[On('cerrar-modal-subdepartamento')]
    public function cerrarModal(?string $mensaje = null): void
    {
        $this->mostrarModal = false;
        $this->editando = null;

        if ($mensaje) {
            $this->dispatch('app-toast', type: 'success', message: $mensaje);
        }

        if ($this->llegoPorRutaDirecta) {
            $this->llegoPorRutaDirecta = false;
            $this->redirect(route('subdepartamentos'), navigate: true);
        }
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function eliminar(int $subDepartmentId): void
    {
        $subDepartment = SubDepartment::findOrFail($subDepartmentId);
        $this->authorize('delete', $subDepartment);

        try {
            app(SubDepartmentService::class)->delete($subDepartment);
        } catch (SubDepartmentNotDeletableException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('app-toast', type: 'error', message: $e->getMessage());

            return;
        }

        session()->flash('ok', 'Subdepartamento eliminado.');
        $this->dispatch('app-toast', type: 'success', message: 'Subdepartamento eliminado.');
    }

    public function render()
    {
        $subDepartamentos = SubDepartment::query()
            ->with('department')
            ->withCount('users')
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->when($this->departamento, fn ($q) => $q->where('department_id', $this->departamento))
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.organization.sub-departamentos.lista-sub-departamentos', [
            'subDepartamentos' => $subDepartamentos,
            'departamentos' => Department::orderBy('nombre')->get(),
        ]);
    }
}
