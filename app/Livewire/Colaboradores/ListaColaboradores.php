<?php

namespace App\Livewire\Colaboradores;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\CapacidadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaColaboradores extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    #[Url]
    public string $rol = '';

    #[Url]
    public string $area = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->esAdmin(), 403);
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function toggleActivo(int $userId): void
    {
        abort_unless(Auth::user()?->esAdmin(), 403);

        $colaborador = User::findOrFail($userId);
        $colaborador->update(['activo' => ! $colaborador->activo]);

        AuditLog::registrar(
            $colaborador->activo ? 'colaborador_activado' : 'colaborador_desactivado',
            $colaborador,
            "Colaborador {$colaborador->name} ".($colaborador->activo ? 'activado' : 'desactivado'),
        );
    }

    public function eliminar(int $userId): void
    {
        abort_unless(Auth::user()?->esAdmin(), 403);

        if ($userId === Auth::id()) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta.');
            $this->dispatch('app-toast', type: 'error', message: 'No puedes eliminar tu propia cuenta.');

            return;
        }

        $colaborador = User::findOrFail($userId);

        if ($colaborador->esSuperAdmin()) {
            session()->flash('error', 'No puedes eliminar una cuenta de Super Administrador.');
            $this->dispatch('app-toast', type: 'error', message: 'No puedes eliminar una cuenta de Super Administrador.');

            return;
        }

        $nombre = $colaborador->name;
        $colaborador->delete();

        AuditLog::registrar('colaborador_eliminado', $colaborador, "Colaborador {$nombre} eliminado.");

        session()->flash('ok', 'Colaborador eliminado.');
        $this->dispatch('app-toast', type: 'success', message: 'Colaborador eliminado.');
    }

    public function render()
    {
        $servicio = app(CapacidadService::class);

        $colaboradores = User::query()
            ->with('departments')
            ->when($this->buscar, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$this->buscar}%")->orWhere('email', 'like', "%{$this->buscar}%")))
            ->when($this->rol, fn ($q) => $q->where('rol', $this->rol))
            ->when($this->area, fn ($q) => $q->where('area', $this->area))
            ->orderBy('name')
            ->paginate(15);

        $colaboradores->getCollection()->transform(function (User $u) use ($servicio) {
            $u->setAttribute('carga', $servicio->cargaSemanaActual($u));

            return $u;
        });

        return view('livewire.colaboradores.lista-colaboradores', [
            'colaboradores' => $colaboradores,
        ]);
    }
}
