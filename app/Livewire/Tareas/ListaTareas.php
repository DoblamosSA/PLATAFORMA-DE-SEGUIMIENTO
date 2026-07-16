<?php

namespace App\Livewire\Tareas;

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
    public string $tipo = '';

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

        $flujo = [
            'pendiente'   => 'en_progreso',
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

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'accion'  => 'cambio_estado',
            'detalle' => "Estado: {$anterior} → {$siguiente}",
        ]);

        $task->proyecto?->recalcularProgreso();
    }

    public function render()
    {
        $tareas = Task::query()
            ->with(['asignado', 'proyecto'])
            ->when($this->buscar, fn ($q) =>
                $q->where('titulo', 'like', "%{$this->buscar}%"))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
            ->when($this->asignado, fn ($q) => $q->where('asignado_id', $this->asignado))
            ->when($this->soloVencidas, fn ($q) => $q->vencidas())
            ->orderByRaw("FIELD(estado, 'pendiente','en_progreso','en_revision','completada','cancelada')")
            ->orderBy('fecha_limite')
            ->paginate(15);

        return view('livewire.tareas.lista-tareas', [
            'tareas'   => $tareas,
            'empleados' => User::where('activo', true)->orderBy('name')->get(),
        ]);
    }
}
