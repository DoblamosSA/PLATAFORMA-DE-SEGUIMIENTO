<?php

namespace App\Livewire\Organization\Departamentos;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Services\DepartmentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaDepartamentos extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Department::class);
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
            ->withCount(['subDepartments', 'users'])
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.organization.departamentos.lista-departamentos', [
            'departamentos' => $departamentos,
        ]);
    }
}
