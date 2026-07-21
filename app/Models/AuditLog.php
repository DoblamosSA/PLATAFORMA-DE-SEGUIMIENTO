<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Bitacora general de trazabilidad para eventos sin tarea asociada
 * (alta/edicion de colaboradores, bloqueos de capacidad, etc). Los eventos
 * ligados a una tarea siguen registrandose en TaskActivity.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'entidad_type',
        'entidad_id',
        'detalle',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function registrar(string $accion, ?Model $entidad, string $detalle): self
    {
        return self::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'entidad_type' => $entidad ? $entidad::class : null,
            'entidad_id' => $entidad?->getKey(),
            'detalle' => $detalle,
        ]);
    }
}
