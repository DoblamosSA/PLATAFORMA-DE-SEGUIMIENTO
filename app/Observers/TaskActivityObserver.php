<?php

namespace App\Observers;

use App\Models\TaskActivity;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Notifica por Web Push los comentarios del foro de una tarea.
 * El resto de acciones de bitacora (cambio de estado, prioridad, etc.)
 * ya generan push via TaskObserver / SubtaskObserver.
 */
class TaskActivityObserver
{
    public function __construct(protected WebPushService $push)
    {
    }

    public function created(TaskActivity $activity): void
    {
        if ($activity->accion !== 'comentario') {
            return;
        }

        $task = $activity->task;
        if (! $task) {
            return;
        }

        $actor = Auth::user();
        $extracto = Str::limit(trim((string) $activity->detalle), 120);
        $cuerpo = ($actor?->name ?? 'Sistema')." comentó en «{$task->titulo}»: {$extracto}";

        if ($task->project_id && $proyecto = $task->proyecto) {
            $cuerpo .= " ({$proyecto->nombre})";
        }

        $url = $task->project_id
            ? "/proyectos/{$task->project_id}/tablero?tarea={$task->id}"
            : "/tareas/{$task->id}/editar";

        $this->push->notificarATodos($actor?->id, 'Nuevo comentario', $cuerpo, $url);
    }
}
