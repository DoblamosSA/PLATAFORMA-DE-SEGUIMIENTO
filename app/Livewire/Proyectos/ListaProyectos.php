<?php

namespace App\Livewire\Proyectos;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
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
    public string $tipo = '';

    #[Url]
    public string $estado = '';

    public function updating($name): void
    {
        $this->resetPage();
    }

    /** Selecciona/deselecciona el filtro por area al pulsar una tarjeta. */
    public function toggleArea(string $tipo): void
    {
        $this->tipo = $this->tipo === $tipo ? '' : $tipo;
        $this->resetPage();
    }

    /**
     * Conteo de proyectos por area (tipo), con desglose activos/completados.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resumenAreas(): array
    {
        $totales      = Project::selectRaw('tipo, COUNT(*) as c')->groupBy('tipo')->pluck('c', 'tipo');
        $completados  = Project::where('estado', 'completado')->selectRaw('tipo, COUNT(*) as c')->groupBy('tipo')->pluck('c', 'tipo');

        return collect(['software', 'soporte', 'infraestructura'])->map(fn ($t) => [
            'tipo'        => $t,
            'total'       => (int) ($totales[$t] ?? 0),
            'completados' => (int) ($completados[$t] ?? 0),
            'activos'     => (int) ($totales[$t] ?? 0) - (int) ($completados[$t] ?? 0),
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
            ->with('responsable')
            ->visiblesPara(Auth::user())
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->latest()
            ->paginate(12);

        return view('livewire.proyectos.lista-proyectos', [
            'proyectos'    => $proyectos,
            'resumenAreas' => $this->resumenAreas(),
            'totalProyectos' => Project::count(),
        ]);
    }
}
