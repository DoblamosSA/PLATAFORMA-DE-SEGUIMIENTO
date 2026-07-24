<?php

namespace App\Livewire\Colaboradores;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\CapacidadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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

    /** Filtro por subdepartamento (antes "area"). */
    #[Url]
    public string $sub_department_id = '';

    public bool $mostrarModal = false;

    public ?User $editando = null;

    public bool $llegoPorRutaDirecta = false;

    public function mount(?User $colaborador = null): void
    {
        abort_unless(Auth::user()?->esAdmin(), 403);

        if (request()->routeIs('colaboradores.crear')) {
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        } elseif ($colaborador?->exists) {
            $this->mostrarModal = true;
            $this->editando = $colaborador;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        $this->editando = null;
        $this->mostrarModal = true;
    }

    public function abrirEditar(int $userId): void
    {
        $this->editando = User::findOrFail($userId);
        $this->mostrarModal = true;
    }

    #[On('cerrar-modal-colaborador')]
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->editando = null;

        if ($this->llegoPorRutaDirecta) {
            $this->llegoPorRutaDirecta = false;
            $this->redirect(route('colaboradores'), navigate: true);
        }
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
            ->with(['departments', 'subDepartments'])
            ->when($this->buscar, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$this->buscar}%")->orWhere('email', 'like', "%{$this->buscar}%")))
            ->when($this->rol, fn ($q) => $q->where('rol', $this->rol))
            ->when($this->sub_department_id, fn ($q) => $q->whereHas('subDepartments', fn ($q2) => $q2->where('sub_departments.id', $this->sub_department_id)))
            ->orderBy('name')
            ->paginate(15);

        $colaboradores->getCollection()->transform(function (User $u) use ($servicio) {
            $u->setAttribute('carga', $servicio->cargaSemanaActual($u));

            return $u;
        });

        return view('livewire.colaboradores.lista-colaboradores', [
            'colaboradores' => $colaboradores,
            'subdepartamentos' => \App\Domain\Organization\Models\SubDepartment::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
