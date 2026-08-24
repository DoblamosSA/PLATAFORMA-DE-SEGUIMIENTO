<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Puntaje, clasificacion y ranking de colaboradores.
 *
 * "Carga"/"Capacidad" son una foto de la carga operativa ACTUAL (misma
 * definicion que el resto de la app: solo tareas abiertas, semana calendario
 * en curso, sin navegacion). El resto de las metricas (tareas, a tiempo,
 * tarde, vencidas, pendientes, cumplimiento, puntaje, clasificacion) es
 * HISTORICO: considera todas las tareas que el colaborador ha tenido
 * asignadas alguna vez (excepto canceladas), sin recortar por semana.
 */
class EvaluadorRendimientoService
{
    public function __construct(
        protected CapacidadService $capacidad,
    ) {}

    /**
     * Todas las tareas (excepto canceladas) que el colaborador ha tenido
     * asignadas alguna vez, sin importar la semana.
     *
     * @return Collection<int, Task>
     */
    public function tareasHistoricas(User $user): Collection
    {
        return Task::query()
            ->where('asignado_id', $user->id)
            ->where('estado', '!=', 'cancelada')
            ->orderByRaw('COALESCE(fecha_asignacion, created_at) asc')
            ->orderBy('id')
            ->get();
    }

    /**
     * Metricas historicas de un colaborador (ver docblock de la clase).
     *
     * @return array{
     *   usuario: User,
     *   carga_asignada: float,
     *   capacidad_disponible: float,
     *   tareas_asignadas: int,
     *   finalizadas_a_tiempo: int,
     *   finalizadas_tarde: int,
     *   vencidas: int,
     *   pendientes: int,
     *   porcentaje_cumplimiento: float,
     *   puntaje: float,
     *   clasificacion: array{clave: string, etiqueta: string, color: string},
     *   desglose: array<string, float|int|string>
     * }
     */
    public function metricasColaborador(User $user): array
    {
        $cargaActual = $this->capacidad->cargaSemanaActual($user);
        $carga = $cargaActual['asignadas'];
        $capacidad = $cargaActual['disponibles'];

        $tareas = $this->tareasHistoricas($user);

        $completadas = $tareas->where('estado', 'completada');
        $aTiempo = $completadas->where('cumplida_a_tiempo', true)->count();
        $tarde = $completadas->filter(fn (Task $t) => $t->cumplida_a_tiempo === false)->count();
        $pendientes = $tareas->whereIn('estado', CierreSemanalService::ESTADOS_PENDIENTES)->count();
        $vencidas = $tareas->filter(fn (Task $t) => $t->estaVencida())->count();
        $asignadas = $tareas->count();

        $horasAsignadas = round((float) $tareas->sum(fn (Task $t) => (float) ($t->horas_estimadas ?? 0)), 2);
        $horasCompletadas = round((float) $completadas->sum(fn (Task $t) => (float) ($t->horas_estimadas ?? 0)), 2);

        $pctCumplimiento = $asignadas > 0
            ? round(($aTiempo / $asignadas) * 100, 1)
            : 100.0;

        $puntualidad = $asignadas > 0 ? ($aTiempo / $asignadas) * 100 : 100.0;
        $sinVencidas = $asignadas > 0 ? (1 - ($vencidas / $asignadas)) * 100 : 100.0;
        $cargaCompletada = $horasAsignadas > 0 ? min(100, ($horasCompletadas / $horasAsignadas) * 100) : 100.0;

        $pesos = config('operativa.puntaje');
        $puntaje = round(
            ($pesos['peso_puntualidad'] * $puntualidad)
            + ($pesos['peso_sin_vencidas'] * $sinVencidas)
            + ($pesos['peso_carga_completada'] * $cargaCompletada),
            1
        );
        $puntaje = max(0, min(100, $puntaje));

        return [
            'usuario' => $user,
            'carga_asignada' => $carga,
            'capacidad_disponible' => $capacidad,
            'tareas_asignadas' => $asignadas,
            'finalizadas_a_tiempo' => $aTiempo,
            'finalizadas_tarde' => $tarde,
            'vencidas' => $vencidas,
            'pendientes' => $pendientes,
            'porcentaje_cumplimiento' => $pctCumplimiento,
            'puntaje' => $puntaje,
            'clasificacion' => $this->clasificar($puntaje),
            'desglose' => [
                'puntualidad' => round($puntualidad, 1),
                'peso_puntualidad' => $pesos['peso_puntualidad'],
                'sin_vencidas' => round($sinVencidas, 1),
                'peso_sin_vencidas' => $pesos['peso_sin_vencidas'],
                'carga_completada' => round($cargaCompletada, 1),
                'peso_carga_completada' => $pesos['peso_carga_completada'],
                'formula' => sprintf(
                    '(%s×%s) + (%s×%s) + (%s×%s)',
                    $pesos['peso_puntualidad'],
                    round($puntualidad, 1),
                    $pesos['peso_sin_vencidas'],
                    round($sinVencidas, 1),
                    $pesos['peso_carga_completada'],
                    round($cargaCompletada, 1),
                ),
            ],
        ];
    }

    /**
     * @param  Collection<int, User>|iterable<User>  $usuarios
     * @return list<array>
     */
    public function ranking(iterable $usuarios): array
    {
        $filas = [];
        foreach ($usuarios as $user) {
            $filas[] = $this->metricasColaborador($user);
        }

        usort($filas, function (array $a, array $b) {
            // 1) Puntaje descendente
            if ($a['puntaje'] !== $b['puntaje']) {
                return $b['puntaje'] <=> $a['puntaje'];
            }
            // 2) Menos vencidas
            if ($a['vencidas'] !== $b['vencidas']) {
                return $a['vencidas'] <=> $b['vencidas'];
            }
            // 3) Mayor % cumplimiento (a tiempo)
            if ($a['porcentaje_cumplimiento'] !== $b['porcentaje_cumplimiento']) {
                return $b['porcentaje_cumplimiento'] <=> $a['porcentaje_cumplimiento'];
            }
            // 4) Nombre
            return strcmp($a['usuario']->name, $b['usuario']->name);
        });

        foreach ($filas as $i => &$fila) {
            $fila['posicion'] = $i + 1;
        }
        unset($fila);

        return $filas;
    }

    /**
     * @return array{clave: string, etiqueta: string, color: string}
     */
    public function clasificar(float $puntaje): array
    {
        foreach (config('operativa.clasificacion') as $banda) {
            if ($puntaje >= $banda['min']) {
                return [
                    'clave' => $banda['clave'],
                    'etiqueta' => $banda['etiqueta'],
                    'color' => $banda['color'],
                ];
            }
        }

        return ['clave' => 'critico', 'etiqueta' => 'Crítico', 'color' => 'rose'];
    }
}
