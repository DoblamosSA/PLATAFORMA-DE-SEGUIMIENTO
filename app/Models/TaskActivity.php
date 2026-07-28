<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskActivity extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'accion',
        'detalle',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(TaskEvidence::class, 'task_activity_id')->oldest();
    }
}
