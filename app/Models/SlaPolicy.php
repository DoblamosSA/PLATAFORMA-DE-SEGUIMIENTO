<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Politica de SLA por tipo + prioridad. Fuente de las horas de
 * resolucion que se aplican al crear una tarea.
 */
class SlaPolicy extends Model
{
    protected $fillable = [
        'tipo',
        'prioridad',
        'horas_resolucion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Devuelve las horas de resolucion configuradas para un tipo/prioridad,
     * o un valor por defecto razonable si no existe politica.
     */
    public static function horasPara(string $tipo, string $prioridad): int
    {
        $policy = static::where('tipo', $tipo)
            ->where('prioridad', $prioridad)
            ->where('activo', true)
            ->first();

        if ($policy) {
            return (int) $policy->horas_resolucion;
        }

        // Respaldo si no hay politica configurada
        return match ($prioridad) {
            'critica' => 4,
            'alta'    => 24,
            'media'   => 72,
            default   => 120,
        };
    }
}
