<?php

namespace App\Livewire\Informes;

use App\Domain\Organization\Models\Department;
use App\Models\User;
use App\Services\EvaluadorRendimientoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Evaluador de colaboradores: rendimiento semanal (solo Admin / SuperAdmin).
 */
#[Layout('layouts.app')]
class EvaluadorColaboradores extends Component
{
    /** Lunes de la semana en formato Y-m-d. */
    #[Url]
    public string $semana = '';

    #[Url]
    public string $colaborador_id = '';

    #[Url]
    public string $department_id = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->puedeVerEvaluador(), 403);

        if ($this->semana === '') {
            $this->semana = now()->startOfWeek()->format('Y-m-d');
        }
    }

    public function semanaAnterior(): void
    {
        $this->semana = Carbon::parse($this->semana)->subWeek()->startOfWeek()->format('Y-m-d');
    }

    public function semanaSiguiente(): void
    {
        $this->semana = Carbon::parse($this->semana)->addWeek()->startOfWeek()->format('Y-m-d');
    }

    public function render(EvaluadorRendimientoService $evaluador)
    {
        $ref = Carbon::parse($this->semana)->startOfWeek();
        [$desde, $hasta] = $evaluador->limitesSemana($ref);

        $user = Auth::user();
        $miDepto = $user->departments()->first()?->id;

        $query = User::query()
            ->where('activo', true)
            ->with(['subDepartments', 'departments'])
            ->orderBy('name');

        if (! $user->esSuperAdmin()) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $miDepto));
        }

        if ($this->department_id !== '') {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', (int) $this->department_id));
        }

        if ($this->colaborador_id !== '') {
            $query->where('id', (int) $this->colaborador_id);
        }

        $usuarios = $query->get();
        $ranking = $evaluador->ranking($usuarios, $ref);

        $departamentos = Department::query()
            ->when(! $user->esSuperAdmin(), fn ($q) => $q->where('id', $miDepto))
            ->orderBy('nombre')
            ->get();

        $colaboradoresFiltro = User::query()
            ->where('activo', true)
            ->when(! $user->esSuperAdmin(), fn ($q) => $q->whereHas('departments', fn ($q2) => $q2->where('departments.id', $miDepto)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.informes.evaluador-colaboradores', [
            'ranking' => $ranking,
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentos' => $departamentos,
            'colaboradoresFiltro' => $colaboradoresFiltro,
            'etiquetaSemana' => $desde->format('d/m/Y').' – '.$hasta->format('d/m/Y'),
        ]);
    }
}
