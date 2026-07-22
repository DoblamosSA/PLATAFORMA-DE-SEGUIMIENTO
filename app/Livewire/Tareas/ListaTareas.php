<?php

namespace App\Livewire\Tareas;

use App\Domain\Organization\Models\SubDepartment;
use App\Models\AuditLog;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ListaTareas extends Component
{
    use WithPagination;

    #[Url]
    public string $buscar = '';

    #[Url]
    public string $estado = '';

    #[Url]
    public string $sub_department_id = '';

    #[Url]
    public string $asignado = '';

    #[Url]
    public bool $soloVencidas = false;

    public function updating($name): void
    {
        // Al cambiar cualquier filtro, volver a la primera pagina
        $this->resetPage();
    }

    /**
     * Avanza la tarea al siguiente estado del flujo.
     */
    public function avanzar(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        $u = Auth::user();
        abort_unless($u && ($u->esCoordinador() || $task->asignado_id === $u->id), 403);

        $flujo = [
            'pendiente' => 'en_progreso',
            'en_progreso' => 'en_revision',
            'en_revision' => 'completada',
        ];

        $anterior = $task->estado;
        $siguiente = $flujo[$anterior] ?? null;

        if (! $siguiente) {
            return;
        }

        if ($siguiente === 'en_progreso' && ! $task->fecha_inicio_real) {
            $task->fecha_inicio_real = now();
        }

        if ($siguiente === 'completada') {
            $task->completar();
        } else {
            $task->estado = $siguiente;
            $task->save();
        }

        // Mantener la card en la columna que corresponde a su nuevo estado.
        if ($task->project_id && $task->proyecto) {
            $task->proyecto->asegurarColumnas();
            $actual = $task->columna;
            if (! $actual || $actual->estado !== $task->estado) {
                if ($columna = $task->proyecto->columnaParaEstado($task->estado)) {
                    $task->board_column_id = $columna->id;
                    $task->posicion = (int) Task::where('board_column_id', $columna->id)->max('posicion') + 1;
                    $task->save();
                }
            }
        }

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'accion' => 'cambio_estado',
            'detalle' => "Estado: {$anterior} → {$siguiente}",
        ]);

        $task->proyecto?->recalcularProgreso();
    }

    public function eliminar(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        abort_unless(Auth::user()?->puedeEliminarTarea($task), 403);

        $nombre = $task->titulo;
        $proyecto = $task->proyecto;

        AuditLog::registrar('tarea_eliminada', null, "Tarea eliminada: {$nombre}");
        $task->delete();
        $proyecto?->recalcularProgreso();

        session()->flash('ok', 'Tarea eliminada.');
    }

    public function render()
    {
        $tareas = Task::query()
            ->with(['asignado', 'proyecto', 'subDepartamento'])
            ->visiblesPara(Auth::user())
            ->when($this->buscar, fn ($q) => $q->where('titulo', 'like', "%{$this->buscar}%"))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->sub_department_id, fn ($q) => $q->where('sub_department_id', $this->sub_department_id))
            ->when($this->asignado, fn ($q) => $q->where('asignado_id', $this->asignado))
            ->when($this->soloVencidas, fn ($q) => $q->vencidas())
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 1 WHEN 'en_progreso' THEN 2 WHEN 'en_revision' THEN 3 WHEN 'completada' THEN 4 WHEN 'cancelada' THEN 5 ELSE 6 END")
            ->orderBy('fecha_limite')
            ->paginate(15);

        return view('livewire.tareas.lista-tareas', [
            'tareas' => $tareas,
            'empleados' => User::where('activo', true)->orderBy('name')->get(),
            'subDepartamentos' => SubDepartment::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
