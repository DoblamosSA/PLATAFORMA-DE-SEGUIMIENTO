<?php

namespace App\Livewire\Organization\Roles;

use App\Domain\Organization\Exceptions\RoleNotDeletableException;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\RoleService;
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
        abort_unless(Gate::allows('roles.view'), 403);

        if (request()->routeIs('roles.crear')) {
            abort_unless(Gate::allows('roles.create'), 403);
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        } elseif ($role?->exists) {
            abort_unless($role->is_primary ? Gate::allows('roles.view') : Gate::allows('roles.edit'), 403);
            $this->mostrarModal = true;
            $this->editando = $role;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        abort_unless(Gate::allows('roles.create'), 403);

        $this->editando = null;
        $this->mostrarModal = true;
    }

    public function abrirEditar(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        // Ver un rol primario como solo-lectura (sin ser SuperAdmin) sigue
        // requiriendo 'roles.view'; editar uno heredado requiere 'roles.edit'.
        abort_unless($role->is_primary ? Gate::allows('roles.view') : Gate::allows('roles.edit'), 403);

        $this->editando = $role;
        $this->mostrarModal = true;
    }

    /** El toast se dispara aqui (no en FormRole): ver el comentario en FormRole::cancelar(). */
    #[On('cerrar-modal-rol')]
    public function cerrarModal(?string $mensaje = null): void
    {
        $this->mostrarModal = false;
        $this->editando = null;

        if ($mensaje) {
            $this->dispatch('app-toast', type: 'success', message: $mensaje);
        }

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
        // Duplicar crea un rol nuevo (una copia).
        abort_unless(Gate::allows('roles.create'), 403);

        $rol = Role::findOrFail($roleId);
        $copia = $service->duplicateRole($rol);

        session()->flash('ok', 'Rol duplicado. Ajusta el nombre y los permisos de la copia.');
        $this->dispatch('app-toast', type: 'success', message: 'Rol duplicado. Ajusta el nombre y los permisos de la copia.');

        return $this->redirect(route('roles.editar', $copia), navigate: true);
    }

    public function eliminar(int $roleId, RoleService $service): void
    {
        abort_unless(Gate::allows('roles.delete'), 403);

        try {
            $service->deleteRole(Role::findOrFail($roleId));
            session()->flash('ok', 'Rol eliminado.');
            $this->dispatch('app-toast', type: 'success', message: 'Rol eliminado.');
        } catch (RoleNotDeletableException $e) {
            session()->flash('error', $e->getMessage());
            $this->dispatch('app-toast', type: 'error', message: $e->getMessage());
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
