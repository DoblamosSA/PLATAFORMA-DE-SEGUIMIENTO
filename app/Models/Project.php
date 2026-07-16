<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'estado',
        'prioridad',
        'responsable_id',
        'fecha_inicio',
        'fecha_fin_estimada',
        'fecha_fin_real',
        'progreso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio'       => 'date',
            'fecha_fin_estimada' => 'date',
            'fecha_fin_real'     => 'date',
            'progreso'           => 'integer',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Equipo del proyecto (desarrolladores asignables a sus tareas). */
    public function equipo(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('rol_en_proyecto')
            ->withTimestamps();
    }

    /**
     * Indicadores de cumplimiento del proyecto completo, calculados
     * a partir de sus tareas.
     *
     * @return array<string, mixed>
     */
    public function metricasCumplimiento(): array
    {
        $completadas = $this->tareas()->where('estado', 'completada')->count();
        $aTiempo     = $this->tareas()->where('estado', 'completada')->where('cumplida_a_tiempo', true)->count();
        $abiertas    = $this->tareas()->abiertas()->count();
        $vencidas    = $this->tareas()->vencidas()->count();

        return [
            'total'        => $this->tareas()->count(),
            'completadas'  => $completadas,
            'a_tiempo'     => $aTiempo,
            'abiertas'     => $abiertas,
            'vencidas'     => $vencidas,
            'cumplimiento' => $completadas > 0 ? round(($aTiempo / $completadas) * 100, 1) : 0.0,
        ];
    }

    /**
     * Recalcula el progreso (% de tareas completadas) y lo persiste.
     */
    public function recalcularProgreso(): void
    {
        $total = $this->tareas()->whereNot('estado', 'cancelada')->count();

        if ($total === 0) {
            $this->update(['progreso' => 0]);
            return;
        }

        $completadas = $this->tareas()->where('estado', 'completada')->count();
        $this->update(['progreso' => (int) round(($completadas / $total) * 100)]);
    }
}
