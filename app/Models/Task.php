<?php

namespace App\Models;

use App\Domain\Organization\Models\SubDepartment;
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
        'sub_department_id',
        'prioridad',
        'estado',
        'board_column_id',
        'posicion',
        'asignado_id',
        'creado_por',
        'fecha_asignacion',
        'fecha_inicio',
        'fecha_limite',
        'fecha_inicio_real',
        'fecha_completada',
        'sla_horas',
        'horas_estimadas',
        'cumplida_a_tiempo',
        'tag',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'fecha_inicio' => 'date',
            'fecha_limite' => 'datetime',
            'fecha_inicio_real' => 'datetime',
            'fecha_completada' => 'datetime',
            'cumplida_a_tiempo' => 'boolean',
            'posicion' => 'integer',
            'horas_estimadas' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function subDepartamento(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
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

    /** Columna del tablero Kanban donde esta ubicada la tarea. */
    public function columna(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'board_column_id');
    }

    /** Solo los comentarios de la bitacora (foro de discusion). */
    public function comentarios(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->where('accion', 'comentario')->latest();
    }

    /** Desglose de la tarea en subtareas con horas estimadas, en orden de creacion. */
    public function subtareas(): HasMany
    {
        return $this->hasMany(Subtask::class)->oldest();
    }

    /**
     * Recalcula horas_estimadas como la suma de las horas de las subtareas
     * y la persiste. Sin subtareas, queda en null.
     */
    public function recalcularHoras(): void
    {
        $total = $this->subtareas()->sum('horas');

        $this->update(['horas_estimadas' => $total > 0 ? $total : null]);
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

    /**
     * Limita el listado a las tareas visibles para el usuario: admin y
     * coordinador ven todas; colaborador y evaluador solo las suyas o las
     * de los proyectos donde participan.
     */
    public function scopeVisiblesPara(Builder $q, User $user): Builder
    {
        if ($user->esCoordinador()) {
            return $q;
        }

        return $q->where(function (Builder $q2) use ($user) {
            $q2->where('asignado_id', $user->id)
                ->orWhereHas('proyecto.equipo', fn (Builder $q3) => $q3->where('users.id', $user->id))
                ->orWhereHas('proyecto', fn (Builder $q3) => $q3->where('responsable_id', $user->id));
        });
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
        $horas = SlaPolicy::horasPara($this->sub_department_id, $this->prioridad);
        $base = $this->fecha_asignacion ?? now();

        $this->sla_horas = $horas;
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

        $this->estado = 'completada';
        $this->fecha_completada = $cuando;
        $this->cumplida_a_tiempo = $this->fecha_limite
            ? $cuando->lessThanOrEqualTo($this->fecha_limite)
            : true;

        $this->save();
    }

    /**
     * El evaluador rechaza una tarea completada: vuelve a quedar abierta
     * (en_revision) y no cuenta para el cumplimiento hasta resolverse.
     */
    public function rechazar(): void
    {
        $this->estado = 'rechazada';
        $this->fecha_completada = null;
        $this->cumplida_a_tiempo = null;
        $this->save();
    }
}
