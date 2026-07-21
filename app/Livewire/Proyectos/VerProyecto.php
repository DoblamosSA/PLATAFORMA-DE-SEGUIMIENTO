<?php

namespace App\Livewire\Proyectos;

use App\Models\Project;
use App\Models\Task;
use App\Services\CapacidadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VerProyecto extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        abort_unless($project->usuarioPuedeGestionar(Auth::user()), 403);

        $this->project = $project;
    }

    /**
     * Cumplimiento de cada integrante del equipo dentro de este proyecto.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cumplimientoEquipo(): array
    {
        $servicio = app(CapacidadService::class);

        return $this->project->equipo->map(function ($u) use ($servicio) {
            $base = Task::where('project_id', $this->project->id)
                ->where('asignado_id', $u->id);

            $completadas = (clone $base)->where('estado', 'completada')->count();
            $aTiempo = (clone $base)->where('estado', 'completada')->where('cumplida_a_tiempo', true)->count();

            return [
                'usuario' => $u,
                'total' => (clone $base)->count(),
                'completadas' => $completadas,
                'a_tiempo' => $aTiempo,
                'abiertas' => (clone $base)->abiertas()->count(),
                'vencidas' => (clone $base)->vencidas()->count(),
                'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : null,
                'carga' => $servicio->cargaSemanaActual($u),
            ];
        })->all();
    }

    public function render()
    {
        $this->project->load('equipo', 'responsable');

        return view('livewire.proyectos.ver-proyecto', [
            'metricas' => $this->project->metricasCumplimiento(),
            'equipo' => $this->cumplimientoEquipo(),
            'tareas' => Task::where('project_id', $this->project->id)
                ->with('asignado')
                ->orderByRaw("CASE estado WHEN 'pendiente' THEN 1 WHEN 'en_progreso' THEN 2 WHEN 'en_revision' THEN 3 WHEN 'completada' THEN 4 WHEN 'cancelada' THEN 5 ELSE 6 END")
                ->orderBy('fecha_limite')
                ->get(),
        ]);
    }
}
