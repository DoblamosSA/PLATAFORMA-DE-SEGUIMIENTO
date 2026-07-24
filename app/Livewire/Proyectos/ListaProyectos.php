<?php

namespace App\Livewire\Proyectos;

use App\Domain\Organization\Models\SubDepartment;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaProyectos extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    #[Url]
    public string $sub_department_id = '';

    #[Url]
    public string $estado = '';

    public bool $mostrarModal = false;

    public bool $llegoPorRutaDirecta = false;

    public function mount(): void
    {
        if (request()->routeIs('proyectos.crear')) {
            $this->mostrarModal = true;
            $this->llegoPorRutaDirecta = true;
        }
    }

    public function abrirCrear(): void
    {
        $this->mostrarModal = true;
    }

    /** El toast se dispara aqui (no en FormProyecto): ver el comentario en FormProyecto::cancelar(). */
    #[On('cerrar-modal-proyecto')]
    public function cerrarModal(?string $mensaje = null): void
    {
        $this->mostrarModal = false;

        if ($mensaje) {
            $this->dispatch('app-toast', type: 'success', message: $mensaje);
        }

        if ($this->llegoPorRutaDirecta) {
            $this->llegoPorRutaDirecta = false;
            $this->redirect(route('proyectos'), navigate: true);
        }
    }

    public function updating($name): void
    {
        $this->resetPage();
    }

    /** Selecciona/deselecciona el filtro por subdepartamento al pulsar una tarjeta. */
    public function toggleSubDepartamento(int $subDepartmentId): void
    {
        $this->sub_department_id = $this->sub_department_id === (string) $subDepartmentId ? '' : (string) $subDepartmentId;
        $this->resetPage();
    }

    /**
     * Conteo de proyectos por subdepartamento, con desglose activos/completados.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resumenAreas(): array
    {
        $totales      = Project::selectRaw('sub_department_id, COUNT(*) as c')->groupBy('sub_department_id')->pluck('c', 'sub_department_id');
        $completados  = Project::where('estado', 'completado')->selectRaw('sub_department_id, COUNT(*) as c')->groupBy('sub_department_id')->pluck('c', 'sub_department_id');

        return SubDepartment::where('activo', true)->orderBy('nombre')->get()->map(fn (SubDepartment $sd) => [
            'subdepartamento' => $sd,
            'total'       => (int) ($totales[$sd->id] ?? 0),
            'completados' => (int) ($completados[$sd->id] ?? 0),
            'activos'     => (int) ($totales[$sd->id] ?? 0) - (int) ($completados[$sd->id] ?? 0),
        ])->all();
    }

    public function render()
    {
        $proyectos = Project::query()
            ->withCount([
                'tareas',
                'tareas as tareas_completadas_count' => fn ($q) => $q->where('estado', 'completada'),
                'tareas as tareas_vencidas_count' => fn ($q) => $q->vencidas(),
                'tareas as tareas_a_tiempo_count' => fn ($q) => $q->where('estado', 'completada')->where('cumplida_a_tiempo', true),
                'tareas as tareas_en_riesgo_count' => fn ($q) => $q->abiertas()
                    ->whereNotNull('fecha_limite')
                    ->whereBetween('fecha_limite', [now(), now()->addDays(Project::DIAS_ALERTA_VENCIMIENTO)]),
                'tareas as tareas_ejecutadas_count' => fn ($q) => $q->whereIn('estado', ['en_progreso', 'en_revision', 'completada']),
            ])
            ->with(['responsable', 'subDepartamento'])
            ->visiblesPara(Auth::user())
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->when($this->sub_department_id, fn ($q) => $q->where('sub_department_id', $this->sub_department_id))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->latest()
            ->paginate(12);

        return view('livewire.proyectos.lista-proyectos', [
            'proyectos'    => $proyectos,
            'resumenAreas' => $this->resumenAreas(),
            'totalProyectos' => Project::count(),
            'subDepartamentos' => SubDepartment::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
