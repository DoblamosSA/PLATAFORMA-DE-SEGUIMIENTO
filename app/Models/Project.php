<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
