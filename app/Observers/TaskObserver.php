<?php

namespace App\Observers;

use App\Livewire\Proyectos\TableroProyecto;
use App\Models\Task;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Auth;

/**
 * Notifica por Web Push cualquier creacion, edicion o eliminacion de tareas
 * a todos los usuarios suscritos (excepto quien hizo el cambio).
 */
class TaskObserver
{
    /**
     * Campos de mecanica interna: los tocan guardados secundarios de una
     * misma accion (reubicar la card en el tablero, recalcular horas desde
     * subtareas, marcas de tiempo automaticas) y no ameritan push propio.
     */
    protected const CAMPOS_INTERNOS = [
        'posicion',
        'updated_at',
        'board_column_id',
        'horas_estimadas',
        'fecha_inicio_real',
        'fecha_completada',
        'cumplida_a_tiempo',
    ];

    /**
     * Tareas ya notificadas en esta peticion. Una accion (avanzar, rechazar,
     * editar) puede guardar la misma tarea varias veces seguidas; solo debe
     * salir un push, el del primer guardado con cambios de negocio.
     *
     * @var array<int, true>
     */
    protected static array $notificadas = [];

    public function __construct(protected WebPushService $push)
    {
    }

    public function created(Task $task): void
    {
        self::$notificadas[$task->id] = true;
        $this->notificar('Nueva tarea', "creó la tarea «{$task->titulo}»", $task);
    }

    public function updated(Task $task): void
    {
        if (isset(self::$notificadas[$task->id])) {
            return;
        }

        $cambios = array_diff(array_keys($task->getChanges()), self::CAMPOS_INTERNOS);
        if (empty($cambios)) {
            return;
        }

        self::$notificadas[$task->id] = true;

        $detalle = $task->wasChanged('estado')
            ? "cambió «{$task->titulo}» a ".(TableroProyecto::ESTADOS_LABEL[$task->estado] ?? $task->estado)
            : "actualizó la tarea «{$task->titulo}»";

        $this->notificar('Tarea actualizada', $detalle, $task);
    }

    public function deleted(Task $task): void
    {
        self::$notificadas[$task->id] = true;
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
            : ($task->project_id ? "/proyectos/{$task->project_id}/tablero?tarea={$task->id}" : "/tareas/{$task->id}/editar");

        $this->push->notificarATodos($actor?->id, $titulo, $cuerpo, $url);
    }
}
