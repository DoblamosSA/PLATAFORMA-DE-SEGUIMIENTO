<?php

namespace App\Services;

use App\Domain\Organization\Models\SubDepartment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula los indicadores de cumplimiento (SLA) que alimentan el dashboard.
 */
class MetricasService
{
    /**
     * Resumen general en un rango de fechas (por fecha de asignacion).
     *
     * @return array<string, mixed>
     */
    public function resumen(?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $desde ??= now()->startOfMonth();
        $hasta ??= now()->endOfDay();

        $enRango = Task::whereBetween('fecha_asignacion', [$desde, $hasta]);

        $total       = (clone $enRango)->count();
        $completadas = (clone $enRango)->where('estado', 'completada')->count();
        $aTiempo     = (clone $enRango)->where('estado', 'completada')->where('cumplida_a_tiempo', true)->count();
        $vencidasCerradas = (clone $enRango)->where('estado', 'completada')->where('cumplida_a_tiempo', false)->count();

        // Tareas abiertas vencidas (independiente del rango, es estado actual)
        $abiertasVencidas = Task::vencidas()->count();
        $abiertas         = Task::abiertas()->count();

        $cumplimiento = $completadas > 0
            ? round(($aTiempo / $completadas) * 100, 1)
            : 0.0;

        return [
            'total'             => $total,
            'completadas'       => $completadas,
            'a_tiempo'          => $aTiempo,
            'vencidas_cerradas' => $vencidasCerradas,
            'abiertas'          => $abiertas,
            'abiertas_vencidas' => $abiertasVencidas,
            'cumplimiento'      => $cumplimiento,
            'tiempo_promedio'   => $this->tiempoPromedioResolucion($desde, $hasta),
        ];
    }

    /**
     * Tiempo promedio de resolucion en horas (tareas completadas en el rango).
     */
    public function tiempoPromedioResolucion(Carbon $desde, Carbon $hasta): ?float
    {
        $tareas = Task::where('estado', 'completada')
            ->whereBetween('fecha_asignacion', [$desde, $hasta])
            ->whereNotNull('fecha_asignacion')
            ->whereNotNull('fecha_completada')
            ->get(['fecha_asignacion', 'fecha_completada']);

        if ($tareas->isEmpty()) {
            return null;
        }

        $horas = $tareas->map(
            fn (Task $t) => $t->fecha_asignacion->diffInMinutes($t->fecha_completada) / 60
        );

        return round($horas->avg(), 1);
    }

    /**
     * Cumplimiento por subdepartamento.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function porSubdepartamento(Carbon $desde, Carbon $hasta): Collection
    {
        return SubDepartment::where('activo', true)->orderBy('nombre')->get()->map(function (SubDepartment $sd) use ($desde, $hasta) {
            $base = Task::where('sub_department_id', $sd->id)
                ->where('estado', 'completada')
                ->whereBetween('fecha_asignacion', [$desde, $hasta]);

            $completadas = (clone $base)->count();
            $aTiempo     = (clone $base)->where('cumplida_a_tiempo', true)->count();

            return [
                'subdepartamento' => $sd,
                'completadas'  => $completadas,
                'a_tiempo'     => $aTiempo,
                'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : 0.0,
                'abiertas'     => Task::abiertas()->where('sub_department_id', $sd->id)->count(),
            ];
        });
    }

    /**
     * Cumplimiento por proyecto (tareas asignadas dentro del rango).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function porProyecto(Carbon $desde, Carbon $hasta): Collection
    {
        return Project::with('responsable')
            ->orderBy('nombre')
            ->get()
            ->map(function (Project $p) use ($desde, $hasta) {
                $base = Task::where('project_id', $p->id)
                    ->whereBetween('fecha_asignacion', [$desde, $hasta]);

                $completadas = (clone $base)->where('estado', 'completada')->count();
                $aTiempo     = (clone $base)->where('estado', 'completada')->where('cumplida_a_tiempo', true)->count();

                return [
                    'proyecto'     => $p,
                    'total'        => (clone $base)->count(),
                    'completadas'  => $completadas,
                    'a_tiempo'     => $aTiempo,
                    'abiertas'     => Task::where('project_id', $p->id)->abiertas()->count(),
                    'vencidas'     => Task::where('project_id', $p->id)->vencidas()->count(),
                    'progreso'     => $p->progreso,
                    'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : null,
                ];
            })
            ->filter(fn ($r) => $r['total'] > 0 || $r['abiertas'] > 0)
            ->values();
    }

    /**
     * Ranking de cumplimiento por persona.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function porPersona(Carbon $desde, Carbon $hasta): Collection
    {
        return User::where('activo', true)
            ->get()
            ->map(function (User $u) use ($desde, $hasta) {
                $base = Task::where('asignado_id', $u->id)
                    ->where('estado', 'completada')
                    ->whereBetween('fecha_asignacion', [$desde, $hasta]);

                $completadas = (clone $base)->count();
                $aTiempo     = (clone $base)->where('cumplida_a_tiempo', true)->count();

                return [
                    'usuario'      => $u,
                    'completadas'  => $completadas,
                    'a_tiempo'     => $aTiempo,
                    'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : null,
                    'abiertas'     => Task::abiertas()->where('asignado_id', $u->id)->count(),
                    'vencidas'     => Task::vencidas()->where('asignado_id', $u->id)->count(),
                ];
            })
            ->filter(fn ($r) => $r['completadas'] > 0 || $r['abiertas'] > 0)
            ->sortByDesc(fn ($r) => $r['cumplimiento'] ?? -1)
            ->values();
    }

    /**
     * Cumplimiento por tipo de trabajo (software, soporte, infraestructura).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function porTipo(Carbon $desde, Carbon $hasta): Collection
    {
        $tipos = ['software', 'soporte', 'infraestructura'];

        return collect($tipos)->map(function (string $tipo) use ($desde, $hasta) {
            $base = Task::where('tipo', $tipo)
                ->where('estado', 'completada')
                ->whereBetween('fecha_asignacion', [$desde, $hasta]);

            $completadas = (clone $base)->count();
            $aTiempo     = (clone $base)->where('cumplida_a_tiempo', true)->count();

            return [
                'tipo'         => $tipo,
                'completadas'  => $completadas,
                'a_tiempo'     => $aTiempo,
                'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : 0.0,
            ];
        });
    }
}
