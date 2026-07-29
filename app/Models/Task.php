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
        'inicio_planificado',
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
            'inicio_planificado' => 'datetime',
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

    /** Evidencias de la descripcion (sin comentario asociado). */
    public function evidenciasDescripcion(): HasMany
    {
        return $this->hasMany(TaskEvidence::class)->whereNull('task_activity_id')->latest();
    }

    /** Todas las evidencias de la tarea. */
    public function evidencias(): HasMany
    {
        return $this->hasMany(TaskEvidence::class)->latest();
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
     * Limita el listado a las tareas del departamento del usuario: los
     * departamentos son independientes entre si, asi que nadie ve tareas de
     * un departamento distinto al suyo (ser responsable, asignado o
     * integrante del equipo del proyecto no es excepcion). Unico bypass
     * universal: SuperAdmin.
     */
    public function scopeVisiblesPara(Builder $q, User $user): Builder
    {
        if ($user->esSuperAdmin()) {
            return $q;
        }

        $departamentoId = $user->departments()->first()?->id;

        return $q->whereHas('subDepartamento', fn (Builder $q2) => $q2->where('department_id', $departamentoId));
    }

    // ---------------------------------------------------------------
    // Logica de SLA
    // ---------------------------------------------------------------

    /**
     * @deprecated La capacidad ya no usa SLA. Se conserva por compatibilidad
     * con datos antiguos; no recalcula fecha_limite ni sla_horas.
     */
    public function aplicarSla(): void
    {
        // Intencionalmente vacio: el vencimiento se deriva del plan de
        // disponibilidad diaria (CapacidadService), no de SlaPolicy.
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
