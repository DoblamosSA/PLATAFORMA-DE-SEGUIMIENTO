<?php

namespace App\Livewire\Organization\Permisos;

use App\Domain\Organization\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaPermisos extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->esSuperAdmin() || Gate::allows('roles.manage'), 403);
    }

    public function render(PermissionRepositoryInterface $permissions)
    {
        return view('livewire.organization.permisos.lista-permisos', [
            'grupos' => $permissions->allGroupedByGrupo(),
        ]);
    }
}
