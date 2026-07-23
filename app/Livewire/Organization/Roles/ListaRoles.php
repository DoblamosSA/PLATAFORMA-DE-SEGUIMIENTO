<?php

namespace App\Livewire\Organization\Roles;

use App\Domain\Organization\Exceptions\RoleNotDeletableException;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaRoles extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    public bool $mostrarModal = false;

    public ?Role $editando = null;

    public bool $llegoPorRutaDirecta = false;

    public function mount(?Role $role = null): void
    {
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);

        if (request()->routeIs('roles.crear')) {
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        } elseif ($role?->exists) {
            $this->mostrarModal = true;
            $this->editando = $role;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        $this->editando = null;
        $this->mostrarModal = true;
    }

    public function abrirEditar(int $roleId): void
    {
        $this->editando = Role::findOrFail($roleId);
        $this->mostrarModal = true;
    }

    #[On('cerrar-modal-rol')]
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->editando = null;

        if ($this->llegoPorRutaDirecta) {
            $this->llegoPorRutaDirecta = false;
            $this->redirect(route('roles'), navigate: true);
        }
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function duplicar(int $roleId, RoleService $service)
    {
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);

        $rol = Role::findOrFail($roleId);
        $copia = $service->duplicateRole($rol);

        session()->flash('ok', 'Rol duplicado. Ajusta el nombre y los permisos de la copia.');

        return $this->redirect(route('roles.editar', $copia), navigate: true);
    }

    public function eliminar(int $roleId, RoleService $service): void
    {
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);

        try {
            $service->deleteRole(Role::findOrFail($roleId));
            session()->flash('ok', 'Rol eliminado.');
        } catch (RoleNotDeletableException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $roles = Role::query()
            ->with(['parent', 'department'])
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->orderByDesc('is_primary')
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.organization.roles.lista-roles', [
            'roles' => $roles,
        ]);
    }
}
