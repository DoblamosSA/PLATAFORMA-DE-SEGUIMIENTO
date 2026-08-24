<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Capacidad operativa por disponibilidad diaria del colaborador.
 *
 * La carga de una semana cuenta las tareas ASIGNADAS en esa semana
 * calendario (fecha_asignacion) que aun estan abiertas (ver
 * ESTADOS_ABIERTOS). Completar o cancelar una tarea libera su cupo de
 * inmediato, sin importar en que semana se asigno.
 */
class CapacidadService
{
    /** Estados abiertos (aun exigen resolucion entre semanas). */
    private const ESTADOS_ABIERTOS = ['pendiente', 'en_progreso', 'en_revision', 'rechazada'];

    public function __construct(
        protected CierreSemanalService $cierre,
    ) {}

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
     * Semana laboral de referencia (lunes–domingo) alrededor de $ref.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function limitesSemana(?Carbon $ref = null): array
    {
        $ref ??= Carbon::now();

        return [
            $ref->copy()->startOfWeek()->startOfDay(),
            $ref->copy()->endOfWeek()->startOfDay(),
        ];
    }

    /**
     * Coloca $horas en los huecos libres de [$desde, $hasta], respetando
     * horas_diarias y la ocupacion previa. Modifica $ocupacion in-place.
     *
     * @param  array<string, float>  $ocupacion
     * @return array{plan: array<string, float>, restante: float}
     */
    public function colocarHoras(User $user, float $horas, Carbon $desde, Carbon $hasta, array &$ocupacion): array
    {
        $restante = round($horas, 2);
        $plan = [];
        $diarias = (float) ($user->horas_diarias ?? 0);

        if ($restante <= 0 || $diarias <= 0) {
            return ['plan' => [], 'restante' => $restante];
        }

        $cursor = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->startOfDay();

        while ($restante > 0.01 && $cursor->lessThanOrEqualTo($fin)) {
            if ($user->trabajaEnDiaSemana($cursor->dayOfWeek)) {
                $key = $cursor->format('Y-m-d');
                $usado = (float) ($ocupacion[$key] ?? 0);
                $libre = round(max(0, $diarias - $usado), 2);

                if ($libre > 0.01) {
                    $colocar = round(min($libre, $restante), 2);
                    $plan[$key] = round(($plan[$key] ?? 0) + $colocar, 2);
                    $ocupacion[$key] = round($usado + $colocar, 2);
                    $restante = round($restante - $colocar, 2);
                }
            }

            $cursor->addDay();
        }

        return ['plan' => $plan, 'restante' => $restante];
    }

    /**
     * Ocupacion dia-a-dia de la semana: coloca las tareas asignadas en la
     * semana (incluye completadas) en orden FIFO. Las horas que no caben
     * tambien cuentan como asignadas (exceso).
     *
     * @return array{ocupacion: array<string, float>, planes: array<int, array<string, float>>, asignadas: float, exceso: float}
     */
    public function ocupacionSemana(User $user, ?int $excluirTaskId = null, ?Carbon $ref = null): array
    {
        [$semanaInicio, $semanaFin] = $this->limitesSemana($ref);
        $ocupacion = [];
        $planes = [];
        $exceso = 0.0;

        foreach ($this->tareasAsignadasEnSemana($user, $excluirTaskId, $ref) as $tarea) {
            $horas = (float) ($tarea->horas_estimadas ?? 0);
            if ($horas <= 0) {
                continue;
            }

            $resultado = $this->colocarHoras($user, $horas, $semanaInicio->copy(), $semanaFin, $ocupacion);
            $planes[$tarea->id] = $resultado['plan'];

            if ($resultado['restante'] > 0.01) {
                $exceso = round($exceso + $resultado['restante'], 2);
            }
        }

        $asignadas = round(array_sum($ocupacion) + $exceso, 2);

        return [
            'ocupacion' => $ocupacion,
            'planes' => $planes,
            'asignadas' => $asignadas,
            'exceso' => $exceso,
        ];
    }

    /**
     * Distribuye las horas estimadas de una tarea segun disponibilidad
     * diaria del colaborador en la semana en curso (huecos libres tras
     * las demas tareas). ['Y-m-d' => horas].
     */
    public function distribucionDiaria(Task $task, User $user): array
    {
        $horas = (float) ($task->horas_estimadas ?? 0);
        if ($horas <= 0 || ! $user->horas_diarias) {
            return [];
        }

        // Plan de esta tarea dentro de la ocupacion semanal (ella incluida).
        $semana = $this->ocupacionSemana($user);
        $plan = $semana['planes'][$task->id] ?? null;
        if ($plan !== null) {
            return $plan;
        }

        // Tarea nueva (sin id en BD) o excluida: colocarla sobre la ocupacion ajena.
        [$semanaInicio, $semanaFin] = $this->limitesSemana();
        $ocupacion = $this->ocupacionSemana($user, $task->id ?: null)['ocupacion'];
        $inicio = $task->fecha_inicio?->copy()->startOfDay() ?? $semanaInicio->copy();
        if ($inicio->lessThan($semanaInicio)) {
            $inicio = $semanaInicio->copy();
        }

        return $this->colocarHoras($user, $horas, $inicio, $semanaFin, $ocupacion)['plan'];
    }

    /**
     * Horas asignadas al colaborador que caen dentro de [desde, hasta].
     */
    public function horasAsignadasPeriodo(User $user, Carbon $desde, Carbon $hasta, ?int $excluirTaskId = null): float
    {
        $desdeKey = $desde->copy()->startOfDay()->format('Y-m-d');
        $hastaKey = $hasta->copy()->startOfDay()->format('Y-m-d');
        $ref = $desde->copy()->startOfWeek();

        // Cubrir el periodo: si cruza semanas, acumular semana a semana.
        $total = 0.0;
        $cursor = $ref->copy();
        $finSemanaUltima = $hasta->copy()->endOfWeek()->startOfDay();

        while ($cursor->lessThanOrEqualTo($finSemanaUltima)) {
            $semana = $this->ocupacionSemana($user, $excluirTaskId, $cursor);
            [$iniSem, $finSem] = $this->limitesSemana($cursor);
            $iniKey = $iniSem->format('Y-m-d');
            $finKey = $finSem->format('Y-m-d');

            foreach ($semana['ocupacion'] as $dia => $horasDia) {
                if ($dia >= $desdeKey && $dia <= $hastaKey) {
                    $total += $horasDia;
                }
            }

            // El exceso semanal cuenta si el periodo cubre esa semana laboral.
            if ($desdeKey <= $iniKey && $hastaKey >= $finKey) {
                $total += $semana['exceso'];
            }

            $cursor->addWeek();
        }

        return round($total, 2);
    }

    /**
     * Carga actual del colaborador sobre la semana en curso.
     *
     * @return array{disponibles: float, asignadas: float, porcentaje: float, estado: string}
     */
    public function cargaSemanaActual(User $user): array
    {
        [$desde, $hasta] = $this->limitesSemana();

        $disponibles = $this->capacidadPeriodo($user, $desde, $hasta);
        $semana = $this->ocupacionSemana($user);
        $asignadas = $semana['asignadas'];
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
     * Valida si $horasSolicitadas caben en el cupo semanal del colaborador
     * (disponibles − ya asignadas). El plan diario se arma desde hoy hacia
     * adelante y, si hace falta, derrama a la semana siguiente: los huecos
     * de dias ya pasados cuentan en el total libre de la semana, pero no
     * se usan para fechar el trabajo nuevo.
     *
     * Firma compatible: el 4.º argumento historico ($hasta / fecha_limite) se
     * ignora si es Carbon; si es int se trata como $excluirTaskId.
     *
     * @return array{ok: bool, disponibles: ?float, asignadas: ?float, solicitadas: float, restante: ?float, mensaje: ?string, plan: array<string, float>}
     */
    public function validarAsignacion(
        User $user,
        float $horasSolicitadas,
        ?Carbon $desde = null,
        Carbon|int|null $hastaOExcluir = null,
        ?int $excluirTaskId = null,
    ): array {
        // Compatibilidad con llamadas antiguas: (user, horas, desde, hasta, excluirId)
        if (is_int($hastaOExcluir)) {
            $excluirTaskId = $hastaOExcluir;
        }

        if ($horasSolicitadas <= 0) {
            return [
                'ok' => true, 'disponibles' => null, 'asignadas' => null,
                'solicitadas' => $horasSolicitadas, 'restante' => null,
                'mensaje' => null, 'plan' => [], 'tarea_bloqueante' => null,
            ];
        }

        $cierre = $this->cierre->validarSinPendientesPrevios($user);
        if (! $cierre['ok']) {
            return [
                'ok' => false,
                'disponibles' => null,
                'asignadas' => null,
                'solicitadas' => $horasSolicitadas,
                'restante' => null,
                'mensaje' => $cierre['mensaje'],
                'plan' => [],
                'tarea_bloqueante' => null,
            ];
        }

        [$semanaInicio, $semanaFin] = $this->limitesSemana();
        $disponibles = $this->capacidadPeriodo($user, $semanaInicio, $semanaFin);

        if ($disponibles <= 0) {
            return [
                'ok' => true, 'disponibles' => 0.0, 'asignadas' => null,
                'solicitadas' => $horasSolicitadas, 'restante' => null, 'plan' => [],
                'mensaje' => "{$user->name} no tiene disponibilidad configurada (días laborales/horas diarias). Completa su perfil de colaborador para validar la capacidad.",
                'tarea_bloqueante' => null,
            ];
        }

        $ocupacionSemana = $this->ocupacionSemana($user, $excluirTaskId);
        $ocupacion = $ocupacionSemana['ocupacion'];
        $asignadas = $ocupacionSemana['asignadas'];
        $restanteHueco = round($disponibles - $asignadas, 2);

        // Criterio de negocio: cupo semanal total (no solo huecos desde hoy).
        // Si quedan 16 h libres en la semana, una tarea de 12 h debe pasar
        // aunque parte de esas 16 h caigan en dias ya transcurridos.
        $ok = ($asignadas + $horasSolicitadas) <= $disponibles + 0.01;

        $plan = [];
        if ($ok) {
            $plan = $this->planificarHorasNuevas(
                $user,
                $horasSolicitadas,
                $desde,
                $semanaInicio,
                $semanaFin,
                $ocupacion,
            );
        }

        $mensaje = null;
        $tareaBloqueante = null;
        if (! $ok) {
            // La tarea "pendiente" a referenciar en el mensaje: la mas antigua
            // (orden FIFO) entre las que realmente ocupan horas de la semana
            // (0 h no consume cupo, ver el mismo filtro en ocupacionSemana) y
            // todavia esta abierta (no completada), para que el enlace lleve
            // a algo que el usuario pueda realmente reducir o reasignar.
            $tareaBloqueante = $this->tareasAsignadasEnSemana($user, $excluirTaskId)
                ->first(fn (Task $t) => in_array($t->estado, self::ESTADOS_ABIERTOS, true)
                    && (float) ($t->horas_estimadas ?? 0) > 0);

            $mensaje = $tareaBloqueante
                ? sprintf('Superas la capacidad de trabajo, pendiente la tarea: %s.', $tareaBloqueante->titulo)
                : sprintf('Superas la capacidad de trabajo semanal de %s.', $user->name);
        }

        return [
            'ok' => $ok,
            'disponibles' => $disponibles,
            'asignadas' => $asignadas,
            'solicitadas' => $horasSolicitadas,
            'restante' => $restanteHueco,
            'mensaje' => $mensaje,
            'plan' => $plan,
            'tarea_bloqueante' => $tareaBloqueante,
        ];
    }

    /**
     * Arma el plan diario para horas nuevas: primero huecos desde hoy hasta
     * el fin de semana; si aun falta (porque habia cupo en dias pasados),
     * derrama a la semana siguiente.
     *
     * @param  array<string, float>  $ocupacion
     * @return array<string, float>
     */
    protected function planificarHorasNuevas(
        User $user,
        float $horas,
        ?Carbon $desde,
        Carbon $semanaInicio,
        Carbon $semanaFin,
        array $ocupacion,
    ): array {
        $inicio = ($desde ?? Carbon::now())->copy()->startOfDay();
        $hoy = Carbon::now()->startOfDay();
        if ($inicio->lessThan($hoy)) {
            $inicio = $hoy->copy();
        }
        if ($inicio->lessThan($semanaInicio)) {
            $inicio = $semanaInicio->copy();
        }

        $resultado = $this->colocarHoras($user, $horas, $inicio, $semanaFin, $ocupacion);
        $plan = $resultado['plan'];

        if ($resultado['restante'] > 0.01) {
            $sigInicio = $semanaFin->copy()->addDay()->startOfDay();
            $sigFin = $sigInicio->copy()->endOfWeek()->startOfDay();
            $extra = $this->colocarHoras($user, $resultado['restante'], $sigInicio, $sigFin, $ocupacion);
            foreach ($extra['plan'] as $dia => $h) {
                $plan[$dia] = round(($plan[$dia] ?? 0) + $h, 2);
            }
        }

        return $plan;
    }

    public function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }

    /**
     * Tareas ABIERTAS del colaborador asignadas en la semana de $ref.
     * Completar o cancelar una tarea libera su cupo de inmediato, sin
     * importar en que semana se asigno.
     *
     * @return Collection<int, Task>
     */
    public function tareasAsignadasEnSemana(User $user, ?int $excluirTaskId = null, ?Carbon $ref = null): Collection
    {
        [$semanaInicio, $semanaFin] = $this->limitesSemana($ref);
        $finExclusivo = $semanaFin->copy()->addDay()->startOfDay();

        $tareas = Task::query()
            ->where('asignado_id', $user->id)
            ->whereIn('estado', self::ESTADOS_ABIERTOS)
            ->when($excluirTaskId, fn ($q) => $q->where('id', '!=', $excluirTaskId))
            ->where(function ($q) use ($semanaInicio, $finExclusivo) {
                $q->where(function ($q2) use ($semanaInicio, $finExclusivo) {
                    $q2->whereNotNull('fecha_asignacion')
                        ->where('fecha_asignacion', '>=', $semanaInicio)
                        ->where('fecha_asignacion', '<', $finExclusivo);
                })->orWhere(function ($q2) use ($semanaInicio, $finExclusivo) {
                    $q2->whereNull('fecha_asignacion')
                        ->where('created_at', '>=', $semanaInicio)
                        ->where('created_at', '<', $finExclusivo);
                });
            })
            ->orderByRaw('COALESCE(fecha_asignacion, created_at) asc')
            ->orderBy('id')
            ->get();

        Log::debug('capacidad.tareas_semana', [
            'user_id' => $user->id,
            'total_tareas' => $tareas->count(),
            'estados' => $tareas->pluck('estado')->all(),
        ]);

        return $tareas;
    }

    /** @deprecated Usar tareasAsignadasEnSemana; se mantiene por compatibilidad interna. */
    protected function tareasActivas(User $user, ?int $excluirTaskId = null): Collection
    {
        return $this->tareasAsignadasEnSemana($user, $excluirTaskId)
            ->whereIn('estado', self::ESTADOS_ABIERTOS)
            ->values();
    }
}
