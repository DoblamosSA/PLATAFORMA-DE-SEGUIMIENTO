<?php

namespace App\Observers;

use App\Models\Subtask;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Auth;

/**
 * Notifica por Web Push la creacion, edicion o eliminacion de subtareas.
 */
class SubtaskObserver
{
    public function __construct(protected WebPushService $push)
    {
    }

    public function created(Subtask $subtask): void
    {
        $this->notificar('Nueva subtarea', 'agregó la subtarea', $subtask);
    }

    public function updated(Subtask $subtask): void
    {
        $this->notificar('Subtarea actualizada', 'actualizó la subtarea', $subtask);
    }

    public function deleted(Subtask $subtask): void
    {
        $this->notificar('Subtarea eliminada', 'eliminó la subtarea', $subtask);
    }

    protected function notificar(string $titulo, string $accion, Subtask $subtask): void
    {
        $actor = Auth::user();
        $tarea = $subtask->tarea;

        $cuerpo = ($actor?->name ?? 'Sistema')." {$accion} «{$subtask->titulo}»";
        if ($tarea) {
            $cuerpo .= " en «{$tarea->titulo}»";
        }

        $url = $tarea?->project_id ? "/proyectos/{$tarea->project_id}/tablero?tarea={$tarea->id}" : '/tareas';

        $this->push->notificarATodos($actor?->id, $titulo, $cuerpo, $url);
    }
}
