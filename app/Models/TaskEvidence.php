<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen de evidencia opcional ligada a una tarea (descripcion) o a un
 * comentario del foro (task_activity_id).
 */
class TaskEvidence extends Model
{
    protected $table = 'task_evidences';

    protected $fillable = [
        'task_id',
        'task_activity_id',
        'user_id',
        'path',
        'nombre_original',
        'mime',
        'bytes',
    ];

    protected function casts(): array
    {
        return [
            'bytes' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(TaskActivity::class, 'task_activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** URL publica relativa (mismo criterio que User::fotoUrl). */
    public function url(): string
    {
        return '/storage/'.$this->path;
    }

    public function eliminarArchivo(): void
    {
        if ($this->path && Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }
    }
}
