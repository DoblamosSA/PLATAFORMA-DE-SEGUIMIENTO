<?php

namespace App\Models;

use App\Domain\Organization\Models\SubDepartment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Politica de SLA por subdepartamento + prioridad. Fuente de las horas de
 * resolucion que se aplican al crear una tarea.
 */
class SlaPolicy extends Model
{
    protected $fillable = [
        'sub_department_id',
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

    public function subDepartamento(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
    }

    /**
     * Devuelve las horas de resolucion configuradas para un subdepartamento/prioridad,
     * o un valor por defecto razonable si no existe politica.
     */
    public static function horasPara(?int $subDepartmentId, string $prioridad): int
    {
        $policy = $subDepartmentId
            ? static::where('sub_department_id', $subDepartmentId)
                ->where('prioridad', $prioridad)
                ->where('activo', true)
                ->first()
            : null;

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
