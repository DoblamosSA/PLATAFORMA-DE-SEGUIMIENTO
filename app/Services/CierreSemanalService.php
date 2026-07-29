<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Impide pendientes entre semanas: un colaborador no puede recibir trabajo
 * nuevo mientras tenga tareas abiertas asignadas en semanas anteriores.
 */
class CierreSemanalService
{
    /** Estados que deben resolverse antes de pasar de semana. */
    public const ESTADOS_PENDIENTES = ['pendiente', 'en_progreso', 'en_revision', 'rechazada'];

    /**
     * @return Collection<int, Task>
     */
    public function pendientesDeSemanasAnteriores(User $user, ?Carbon $ref = null): Collection
    {
        $ref ??= Carbon::now();
        $inicioSemanaActual = $ref->copy()->startOfWeek()->startOfDay();

        return Task::query()
            ->where('asignado_id', $user->id)
            ->whereIn('estado', self::ESTADOS_PENDIENTES)
            ->where(function ($q) use ($inicioSemanaActual) {
                $q->where(function ($q2) use ($inicioSemanaActual) {
                    $q2->whereNotNull('fecha_asignacion')
                        ->where('fecha_asignacion', '<', $inicioSemanaActual);
                })->orWhere(function ($q2) use ($inicioSemanaActual) {
                    $q2->whereNull('fecha_asignacion')
                        ->where('created_at', '<', $inicioSemanaActual);
                });
            })
            ->orderByRaw('COALESCE(fecha_asignacion, created_at) asc')
            ->get();
    }

    public function tienePendientesPrevios(User $user, ?Carbon $ref = null): bool
    {
        return $this->pendientesDeSemanasAnteriores($user, $ref)->isNotEmpty();
    }

    /**
     * @return array{ok: bool, pendientes: int, mensaje: ?string}
     */
    public function validarSinPendientesPrevios(User $user, ?Carbon $ref = null): array
    {
        $pendientes = $this->pendientesDeSemanasAnteriores($user, $ref);
        $n = $pendientes->count();

        if ($n === 0) {
            return ['ok' => true, 'pendientes' => 0, 'mensaje' => null];
        }

        return [
            'ok' => false,
            'pendientes' => $n,
            'mensaje' => sprintf(
                '%s tiene %d tarea(s) pendiente(s) de semanas anteriores. Debe completarlas, certificarlas, cancelarlas o reasignarlas antes de asignar trabajo en la semana actual.',
                $user->name,
                $n,
            ),
        ];
    }
}
