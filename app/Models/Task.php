<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'titulo',
        'descripcion',
        'tipo',
        'prioridad',
        'estado',
        'asignado_id',
        'creado_por',
        'fecha_asignacion',
        'fecha_limite',
        'fecha_inicio_real',
        'fecha_completada',
        'sla_horas',
        'cumplida_a_tiempo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion'  => 'datetime',
            'fecha_limite'      => 'datetime',
            'fecha_inicio_real' => 'datetime',
            'fecha_completada'  => 'datetime',
            'cumplida_a_tiempo' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function asignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeAbiertas(Builder $q): Builder
    {
        return $q->whereNotIn('estado', ['completada', 'cancelada']);
    }

    public function scopeCerradas(Builder $q): Builder
    {
        return $q->where('estado', 'completada');
    }

    /** Tareas abiertas cuya fecha limite ya paso. */
    public function scopeVencidas(Builder $q): Builder
    {
        return $q->abiertas()
            ->whereNotNull('fecha_limite')
            ->where('fecha_limite', '<', now());
    }

    // ---------------------------------------------------------------
    // Logica de SLA
    // ---------------------------------------------------------------

    /**
     * Calcula y asigna la fecha limite a partir de la politica de SLA
     * vigente para el tipo/prioridad actuales. Usa la fecha de asignacion
     * (o ahora) como punto de partida.
     */
    public function aplicarSla(): void
    {
        $horas = SlaPolicy::horasPara($this->tipo, $this->prioridad);
        $base  = $this->fecha_asignacion ?? now();

        $this->sla_horas   = $horas;
        $this->fecha_limite = $base->copy()->addHours($horas);
    }

    /**
     * True si la tarea esta abierta y ya paso su fecha limite.
     */
    public function estaVencida(): bool
    {
        return $this->estado !== 'completada'
            && $this->estado !== 'cancelada'
            && $this->fecha_limite
            && $this->fecha_limite->isPast();
    }

    /**
     * Horas transcurridas entre asignacion y cierre (o ahora si sigue abierta).
     */
    public function horasTranscurridas(): ?float
    {
        if (! $this->fecha_asignacion) {
            return null;
        }

        $fin = $this->fecha_completada ?? now();

        return round($this->fecha_asignacion->diffInMinutes($fin) / 60, 1);
    }

    /**
     * Marca la tarea como completada y evalua el cumplimiento del SLA.
     */
    public function completar(?Carbon $cuando = null): void
    {
        $cuando ??= now();

        $this->estado            = 'completada';
        $this->fecha_completada  = $cuando;
        $this->cumplida_a_tiempo = $this->fecha_limite
            ? $cuando->lessThanOrEqualTo($this->fecha_limite)
            : true;

        $this->save();
    }
}
