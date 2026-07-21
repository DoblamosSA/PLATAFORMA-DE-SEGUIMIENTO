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

    public function render()
    {
        $servicio = app(CapacidadService::class);

        $colaboradores = User::query()
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
