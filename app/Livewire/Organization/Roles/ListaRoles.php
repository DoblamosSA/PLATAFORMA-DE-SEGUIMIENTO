<?php

namespace App\Livewire\Organization\Roles;

use App\Domain\Organization\Exceptions\RoleNotDeletableException;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaRoles extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);
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
