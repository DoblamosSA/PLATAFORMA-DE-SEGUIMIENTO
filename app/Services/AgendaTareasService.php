<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Calcula, solo para efectos de reporte (nada se escribe en BD), la agenda de
 * una tarea: cuando empieza y termina cada una de sus "unidades de duracion"
 * (sus Subtask, o una unidad sintetica con horas_estimadas si no tiene
 * subtareas), encadenadas de forma continua a partir de Task.fecha_inicio,
 * consumiendo la capacidad diaria del colaborador asignado (horas_diarias) y
 * respetando sus dias_laborales.
 *
 * Las fechas guardadas de Task/Subtask siguen siendo 100% manuales: este
 * servicio no las modifica ni las lee para nada mas que fecha_inicio como
 * ancla del calculo.
 */
class AgendaTareasService
{
    /**
     * @return array<int, array{etiqueta: string, horas: float, fecha_inicio: Carbon, fecha_fin: Carbon}>
     */
    public function agendaDeTarea(Task $task): array
    {
        $colaborador = $task->asignado;

        if (! $colaborador || ! $task->fecha_inicio) {
            Log::warning('agenda.tarea_sin_datos_suficientes', [
                'task_id' => $task->id,
                'tiene_colaborador' => (bool) $colaborador,
                'tiene_fecha_inicio' => (bool) $task->fecha_inicio,
            ]);

            return [];
        }

        $unidades = $this->unidadesDeDuracion($task);
        $cursor = Carbon::parse($task->fecha_inicio)->setTime(
            (int) config('operativa.hora_inicio_jornada', 8),
            (int) config('operativa.minuto_inicio_jornada', 0),
            0,
        );

        $agenda = [];
        foreach ($unidades as $unidad) {
            [$inicio, $fin] = $this->avanzarVentana($colaborador, $unidad['horas'], $cursor);

            $agenda[] = [
                'etiqueta' => $unidad['etiqueta'],
                'horas' => $unidad['horas'],
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
            ];

            Log::debug('agenda.unidad_calculada', [
                'task_id' => $task->id,
                'colaborador_id' => $colaborador->id,
                'etiqueta' => $unidad['etiqueta'],
                'horas' => $unidad['horas'],
                'fecha_inicio' => $inicio->toDateTimeString(),
                'fecha_fin' => $fin->toDateTimeString(),
            ]);

            $cursor = $fin->copy();
        }

        return $agenda;
    }

    /**
     * @return array<int, array{etiqueta: string, horas: float}>
     */
    private function unidadesDeDuracion(Task $task): array
    {
        if ($task->subtareas->isNotEmpty()) {
            return $task->subtareas
                ->map(fn ($subtarea) => [
                    'etiqueta' => $subtarea->titulo,
                    'horas' => (float) $subtarea->horas,
                ])
                ->all();
        }

        return [[
            'etiqueta' => $task->titulo,
            'horas' => (float) $task->horas_estimadas,
        ]];
    }

    /**
     * Ancla $ref al inicio de jornada del mismo dia si es dia laboral y $ref
     * ya cae dentro de la jornada (permite continuar a mitad de dia); si no
     * es dia laboral, salta al inicio de jornada del siguiente dia laboral.
     */
    private function inicioEfectivo(User $colaborador, Carbon $ref): Carbon
    {
        $ref = $ref->copy();
        $hora = (int) config('operativa.hora_inicio_jornada', 8);
        $minuto = (int) config('operativa.minuto_inicio_jornada', 0);

        for ($i = 0; $i < 14; $i++) {
            if (! $colaborador->trabajaEnDiaSemana($ref->dayOfWeek)) {
                $ref->addDay()->setTime($hora, $minuto, 0);

                continue;
            }

            $inicioJornada = $ref->copy()->setTime($hora, $minuto, 0);
            $finJornada = $inicioJornada->copy()->addHours((float) ($colaborador->horas_diarias ?? 0));

            if ($ref->lessThan($inicioJornada)) {
                return $inicioJornada;
            }

            if ($ref->lessThan($finJornada)) {
                return $ref->copy()->second(0);
            }

            $ref->addDay()->setTime($hora, $minuto, 0);
        }

        return $ref->copy()->setTime($hora, $minuto, 0);
    }

    /**
     * Consume $horas dia por dia dentro de la jornada de horas_diarias del
     * colaborador, saltando dias no laborales, devolviendo [inicio, fin]
     * exactos (fin puede caer a mitad de un dia laboral).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function avanzarVentana(User $colaborador, float $horas, Carbon $desde): array
    {
        $diarias = (float) ($colaborador->horas_diarias ?? 0);
        $inicio = $this->inicioEfectivo($colaborador, $desde);
        $hora = (int) config('operativa.hora_inicio_jornada', 8);
        $minuto = (int) config('operativa.minuto_inicio_jornada', 0);

        if ($horas <= 0 || $diarias <= 0) {
            if ($diarias <= 0) {
                Log::warning('agenda.colaborador_sin_perfil_operativo', [
                    'colaborador_id' => $colaborador->id,
                    'horas_diarias' => $diarias,
                ]);
            }

            return [$inicio, $inicio->copy()];
        }

        $restante = round($horas, 2);
        $cursor = $inicio->copy();
        $fin = $inicio->copy();

        for ($guard = 0; $guard < 366 && $restante > 0.01; $guard++) {
            if (! $colaborador->trabajaEnDiaSemana($cursor->dayOfWeek)) {
                $cursor->addDay()->setTime($hora, $minuto, 0);

                continue;
            }

            $inicioJornada = $cursor->copy()->setTime($hora, $minuto, 0);
            $finJornada = $inicioJornada->copy()->addHours($diarias);

            if ($cursor->lessThan($inicioJornada)) {
                $cursor = $inicioJornada->copy();
            }

            if ($cursor->greaterThanOrEqualTo($finJornada)) {
                $cursor->addDay()->setTime($hora, $minuto, 0);

                continue;
            }

            $libreHoras = round($cursor->floatDiffInHours($finJornada), 2);
            $usar = round(min($libreHoras, $restante), 2);
            if ($usar <= 0) {
                $cursor->addDay()->setTime($hora, $minuto, 0);

                continue;
            }

            $fin = $cursor->copy()->addHours($usar);
            $restante = round($restante - $usar, 2);
            $cursor = $fin->copy();
        }

        return [$inicio, $fin];
    }
}
