<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Columna de un tablero Kanban. Pertenece a un proyecto, es ordenable y
 * mapea a un estado canonico de tarea (ver migracion). Al mover una card
 * a esta columna, la tarea adopta su estado asociado.
 */
class BoardColumn extends Model
{
    protected $fillable = [
        'project_id',
        'nombre',
        'estado',
        'posicion',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** Tareas ubicadas en esta columna, ordenadas por su posicion. */
    public function tareas(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('posicion');
    }
}
