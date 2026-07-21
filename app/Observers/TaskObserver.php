<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Auth;

/**
 * Notifica por Web Push cualquier creacion, edicion o eliminacion de tareas
 * a todos los usuarios suscritos (excepto quien hizo el cambio).
 */
class TaskObserver
{
    public function __construct(protected WebPushService $push)
    {
    }

    public function created(Task $task): void
    {
        $this->notificar('Nueva tarea', "creó la tarea «{$task->titulo}»", $task);
    }

    public function updated(Task $task): void
    {
        // Ignorar cambios puramente posicionales del tablero (arrastre dentro
        // de la misma columna): no aportan informacion al equipo.
        $cambios = array_diff(array_keys($task->getChanges()), ['posicion', 'updated_at']);
        if (empty($cambios)) {
            return;
        }

        $detalle = $task->wasChanged('estado')
            ? "cambió «{$task->titulo}» a estado {$task->estado}"
            : "actualizó la tarea «{$task->titulo}»";

        $this->notificar('Tarea actualizada', $detalle, $task);
    }

    public function deleted(Task $task): void
    {
        $this->notificar('Tarea eliminada', "eliminó la tarea «{$task->titulo}»", $task, borrada: true);
    }

    protected function notificar(string $titulo, string $accion, Task $task, bool $borrada = false): void
    {
        $actor = Auth::user();
        $cuerpo = ($actor?->name ?? 'Sistema').' '.$accion;

        if ($task->project_id && $proyecto = $task->proyecto) {
            $cuerpo .= " ({$proyecto->nombre})";
        }

        $url = $borrada
            ? ($task->project_id ? "/proyectos/{$task->project_id}/tablero" : '/tareas')
            : ($task->project_id ? "/proyectos/{$task->project_id}/tablero" : "/tareas/{$task->id}/editar");

        $this->push->notificarATodos($actor?->id, $titulo, $cuerpo, $url);
    }
}
