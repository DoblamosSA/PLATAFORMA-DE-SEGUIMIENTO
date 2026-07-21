<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Calcula la capacidad operativa de un colaborador (horas disponibles segun
 * sus dias laborales y horas diarias) y su carga de trabajo (horas
 * asignadas de tareas abiertas), distribuyendo las horas estimadas de cada
 * tarea entre sus dias laborables cuando tiene fecha de inicio y vencimiento.
 */
class CapacidadService
{
    /** Estados de tarea que consumen capacidad (excluye cerradas/canceladas). */
    private const ESTADOS_ACTIVOS = ['pendiente', 'en_progreso', 'en_revision', 'rechazada'];

    /** Horas disponibles de un colaborador entre dos fechas (inclusive), segun sus dias laborales. */
    public function capacidadPeriodo(User $user, Carbon $desde, Carbon $hasta): float
    {
        if (empty($user->dias_laborales) || ! $user->horas_diarias) {
            return 0.0;
        }

        $dias = 0;
        $cursor = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($fin)) {
            if ($user->trabajaEnDiaSemana($cursor->dayOfWeek)) {
                $dias++;
            }
            $cursor->addDay();
        }

        return round($dias * (float) $user->horas_diarias, 2);
    }

    /**
     * Distribuye las horas estimadas de una tarea entre los dias laborables
     * del colaborador asignado: ['Y-m-d' => horas del dia].
     *
     * - Con fecha_inicio y fecha_limite: reparte en partes iguales entre los
     *   dias laborables del rango (si ninguno cae en dia laboral, toda la
     *   carga se ubica en la fecha limite).
     * - Solo con fecha_limite: toda la carga cae en ese dia.
     * - Sin ninguna fecha: no se puede ubicar, se ignora en el calculo.
     */
    public function distribucionDiaria(Task $task, User $user): array
    {
        $horas = (float) ($task->horas_estimadas ?? 0);
        if ($horas <= 0) {
            return [];
        }

        $limite = $task->fecha_limite?->copy()->startOfDay();

        if ($task->fecha_inicio && $limite) {
            $inicio = $task->fecha_inicio->copy()->startOfDay();
            if ($inicio->greaterThan($limite)) {
                $inicio = $limite->copy();
            }

            $diasLaborables = [];
            $cursor = $inicio->copy();
            while ($cursor->lessThanOrEqualTo($limite)) {
                if ($user->trabajaEnDiaSemana($cursor->dayOfWeek)) {
                    $diasLaborables[] = $cursor->format('Y-m-d');
                }
                $cursor->addDay();
            }

            if (empty($diasLaborables)) {
                return [$limite->format('Y-m-d') => $horas];
            }

            $porDia = round($horas / count($diasLaborables), 2);

            return array_fill_keys($diasLaborables, $porDia);
        }

        if ($limite) {
            return [$limite->format('Y-m-d') => $horas];
        }

        return [];
    }

    /**
     * Horas asignadas al colaborador que caen dentro de [desde, hasta],
     * sumando la porcion distribuida de cada tarea abierta. Permite excluir
     * una tarea (la que se esta editando) para previsualizar el resultado
     * de una reasignacion antes de guardar.
     */
    public function horasAsignadasPeriodo(User $user, Carbon $desde, Carbon $hasta, ?int $excluirTaskId = null): float
    {
        $desdeKey = $desde->copy()->startOfDay()->format('Y-m-d');
        $hastaKey = $hasta->copy()->startOfDay()->format('Y-m-d');

        $tareas = Task::where('asignado_id', $user->id)
            ->whereIn('estado', self::ESTADOS_ACTIVOS)
            ->when($excluirTaskId, fn ($q) => $q->where('id', '!=', $excluirTaskId))
            ->get();

        $total = 0.0;
        foreach ($tareas as $tarea) {
            foreach ($this->distribucionDiaria($tarea, $user) as $dia => $horasDia) {
                if ($dia >= $desdeKey && $dia <= $hastaKey) {
                    $total += $horasDia;
                }
            }
        }

        return round($total, 2);
    }

    /**
     * Carga actual del colaborador sobre la semana en curso (lunes a
     * domingo): la que se muestra en el panel de equipo del proyecto.
     *
     * @return array{disponibles: float, asignadas: float, porcentaje: float, estado: string}
     */
    public function cargaSemanaActual(User $user): array
    {
        $desde = Carbon::now()->startOfWeek();
        $hasta = Carbon::now()->endOfWeek();

        $disponibles = $this->capacidadPeriodo($user, $desde, $hasta);
        $asignadas = $this->horasAsignadasPeriodo($user, $desde, $hasta);
        $porcentaje = $disponibles > 0 ? round(($asignadas / $disponibles) * 100) : ($asignadas > 0 ? 100.0 : 0.0);

        return [
            'disponibles' => $disponibles,
            'asignadas' => $asignadas,
            'porcentaje' => $porcentaje,
            'estado' => $this->estadoCarga($porcentaje),
        ];
    }

    /** disponible <75% · alta 75-99% · al_limite >=100% */
    public function estadoCarga(float $porcentaje): string
    {
        if ($porcentaje >= 100) {
            return 'al_limite';
        }

        if ($porcentaje >= 75) {
            return 'alta';
        }

        return 'disponible';
    }

    /**
     * Valida si asignar $horasSolicitadas al colaborador en el periodo
     * [$desde, $hasta] supera su capacidad. Sin fecha limite, o sin
     * disponibilidad configurada, no se puede validar y se permite la
     * asignacion dejando constancia de que faltan datos.
     *
     * @return array{ok: bool, disponibles: ?float, asignadas: ?float, solicitadas: float, restante: ?float, mensaje: ?string}
     */
    public function validarAsignacion(
        User $user,
        float $horasSolicitadas,
        ?Carbon $desde,
        ?Carbon $hasta,
        ?int $excluirTaskId = null,
    ): array {
        if (! $hasta || $horasSolicitadas <= 0) {
            return [
                'ok' => true, 'disponibles' => null, 'asignadas' => null,
                'solicitadas' => $horasSolicitadas, 'restante' => null, 'mensaje' => null,
            ];
        }

        $desde ??= $hasta;
        if ($desde->greaterThan($hasta)) {
            $desde = $hasta->copy();
        }

        $disponibles = $this->capacidadPeriodo($user, $desde, $hasta);

        if ($disponibles <= 0) {
            return [
                'ok' => true, 'disponibles' => 0.0, 'asignadas' => null,
                'solicitadas' => $horasSolicitadas, 'restante' => null,
                'mensaje' => "{$user->name} no tiene disponibilidad configurada (días laborales/horas diarias). Completa su perfil de colaborador para validar la capacidad.",
            ];
        }

        $asignadas = $this->horasAsignadasPeriodo($user, $desde, $hasta, $excluirTaskId);
        $total = $asignadas + $horasSolicitadas;
        $restante = round($disponibles - $asignadas, 2);
        $ok = $total <= $disponibles + 0.01; // tolerancia de redondeo

        $mensaje = null;
        if (! $ok) {
            $mensaje = sprintf(
                'Capacidad excedida para %s: dispone de %s h y ya tiene %s h asignadas (quedan %s h libres) en el período %s–%s, pero se solicitan %s h. Cambia la fecha, reduce la duración o elige otro responsable.',
                $user->name,
                $this->fmt($disponibles),
                $this->fmt($asignadas),
                $this->fmt(max($restante, 0)),
                $desde->format('d/m/Y'),
                $hasta->format('d/m/Y'),
                $this->fmt($horasSolicitadas),
            );
        }

        return [
            'ok' => $ok,
            'disponibles' => $disponibles,
            'asignadas' => $asignadas,
            'solicitadas' => $horasSolicitadas,
            'restante' => $restante,
            'mensaje' => $mensaje,
        ];
    }

    public function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }
}
