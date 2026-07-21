<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Desglose de una tarea en subtareas con horas estimadas. La suma de horas
 * de las subtareas de una tarea actualiza automaticamente sus horas
 * estimadas totales (ver Task::recalcularHoras()).
 */
class Subtask extends Model
{
    protected $fillable = [
        'task_id',
        'titulo',
        'horas',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'horas' => 'decimal:2',
        ];
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
