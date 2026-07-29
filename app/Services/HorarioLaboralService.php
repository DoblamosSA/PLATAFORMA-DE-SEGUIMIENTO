<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Calcula inicio/fin planificados de una tarea respetando la jornada laboral
 * del colaborador (dias_laborales + horas_diarias + hora de inicio centralizada).
 *
 * Ejemplo: 9 h desde lun 08:00 con jornada de 8 h → lun 08:00–16:00 y
 * mar 08:00–09:00 (no asume 9 h corridas de reloj).
 */
class HorarioLaboralService
{
    public function horaInicioJornada(): int
    {
        return (int) config('operativa.hora_inicio_jornada', 8);
    }

    public function minutoInicioJornada(): int
    {
        return (int) config('operativa.minuto_inicio_jornada', 0);
    }

    /**
     * Ancla $ref al inicio de jornada del mismo dia si es dia laboral;
     * si no, al inicio de jornada del siguiente dia laboral.
     */
    public function inicioEfectivo(User $user, ?Carbon $ref = null): Carbon
    {
        $ref = ($ref ?? Carbon::now())->copy();
        $hora = $this->horaInicioJornada();
        $minuto = $this->minutoInicioJornada();

        // Si el ref ya es un dia laboral y es antes del fin de jornada, partir
        // del max(ref, inicio de jornada). Si el dia no es laboral, saltar.
        for ($i = 0; $i < 14; $i++) {
            if (! $user->trabajaEnDiaSemana($ref->dayOfWeek)) {
                $ref->addDay()->setTime($hora, $minuto, 0);

                continue;
            }

            $inicioJornada = $ref->copy()->setTime($hora, $minuto, 0);
            $finJornada = $inicioJornada->copy()->addHours((float) ($user->horas_diarias ?? 0));

            if ($ref->lessThan($inicioJornada)) {
                return $inicioJornada;
            }

            if ($ref->lessThan($finJornada)) {
                return $ref->copy()->second(0);
            }

            $ref->addDay()->setTime($hora, $minuto, 0);
        }

        return ($ref ?? Carbon::now())->copy()->setTime($hora, $minuto, 0);
    }

    /**
     * Calcula [inicio, fin] consumiendo $horas solo dentro de jornadas laborales.
     *
     * @return array{0: Carbon, 1: Carbon, plan: array<string, float>}
     */
    public function calcularVentana(User $user, float $horas, ?Carbon $desde = null): array
    {
        $diarias = (float) ($user->horas_diarias ?? 0);
        $inicio = $this->inicioEfectivo($user, $desde);
        $plan = [];

        if ($horas <= 0 || $diarias <= 0) {
            return [$inicio, $inicio->copy(), $plan];
        }

        $restante = round($horas, 2);
        $cursor = $inicio->copy();
        $fin = $inicio->copy();

        for ($guard = 0; $guard < 366 && $restante > 0.01; $guard++) {
            if (! $user->trabajaEnDiaSemana($cursor->dayOfWeek)) {
                $cursor->addDay()->setTime($this->horaInicioJornada(), $this->minutoInicioJornada(), 0);

                continue;
            }

            $inicioJornada = $cursor->copy()->setTime($this->horaInicioJornada(), $this->minutoInicioJornada(), 0);
            $finJornada = $inicioJornada->copy()->addHours($diarias);

            if ($cursor->lessThan($inicioJornada)) {
                $cursor = $inicioJornada->copy();
            }

            if ($cursor->greaterThanOrEqualTo($finJornada)) {
                $cursor->addDay()->setTime($this->horaInicioJornada(), $this->minutoInicioJornada(), 0);

                continue;
            }

            $libreHoras = round($cursor->floatDiffInHours($finJornada), 2);
            $usar = round(min($libreHoras, $restante), 2);
            if ($usar <= 0) {
                $cursor->addDay()->setTime($this->horaInicioJornada(), $this->minutoInicioJornada(), 0);

                continue;
            }

            $key = $cursor->format('Y-m-d');
            $plan[$key] = round(($plan[$key] ?? 0) + $usar, 2);
            $fin = $cursor->copy()->addHours($usar);
            $restante = round($restante - $usar, 2);
            $cursor = $fin->copy();
        }

        return [$inicio, $fin, $plan];
    }
}
