<?php

namespace App\Livewire\Organization\SubDepartamentos;

use App\Domain\Organization\Exceptions\SubDepartmentNotDeletableException;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\SubDepartment;
use App\Domain\Organization\Services\SubDepartmentService;
use Livewire\Attributes\Layout;
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

    public function mount(): void
    {
        $this->authorize('viewAny', SubDepartment::class);
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

            return;
        }

        session()->flash('ok', 'Subdepartamento eliminado.');
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
