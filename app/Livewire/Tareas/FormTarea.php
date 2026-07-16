<?php

namespace App\Livewire\Tareas;

use App\Models\Project;
use App\Models\SlaPolicy;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormTarea extends Component
{
    public ?Task $task = null;

    // Campos del formulario
    public ?int $project_id = null;
    public string $titulo = '';
    public string $descripcion = '';
    public string $tipo = 'soporte';
    public string $prioridad = 'media';
    public string $estado = 'pendiente';
    public ?int $asignado_id = null;

    public function mount(?Task $task = null): void
    {
        if ($task?->exists) {
            $this->task        = $task;
            $this->project_id  = $task->project_id;
            $this->titulo      = $task->titulo;
            $this->descripcion = $task->descripcion ?? '';
            $this->tipo        = $task->tipo;
            $this->prioridad   = $task->prioridad;
            $this->estado      = $task->estado;
            $this->asignado_id = $task->asignado_id;
        }
    }

    protected function rules(): array
    {
        return [
            'titulo'      => 'required|string|min:3|max:255',
            'descripcion' => 'nullable|string',
            'tipo'        => 'required|in:software,soporte,infraestructura',
            'prioridad'   => 'required|in:baja,media,alta,critica',
            'estado'      => 'required|in:pendiente,en_progreso,en_revision,completada,cancelada',
            'project_id'  => 'nullable|exists:projects,id',
            'asignado_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Vista previa de las horas de SLA segun tipo/prioridad actuales.
     */
    public function getSlaHorasProperty(): int
    {
        return SlaPolicy::horasPara($this->tipo, $this->prioridad);
    }

    public function save()
    {
        $this->validate();

        $esNueva = ! $this->task;

        $task = $this->task ?? new Task([
            'creado_por'       => Auth::id(),
            'fecha_asignacion' => now(),
        ]);

        $estadoAnterior = $task->estado;
        $tipoOPrioridadCambio = $task->tipo !== $this->tipo || $task->prioridad !== $this->prioridad;

        $task->fill([
            'project_id'  => $this->project_id,
            'titulo'      => $this->titulo,
            'descripcion' => $this->descripcion,
            'tipo'        => $this->tipo,
            'prioridad'   => $this->prioridad,
            'estado'      => $this->estado,
            'asignado_id' => $this->asignado_id,
        ]);

        // (Re)calcular SLA al crear o si cambio tipo/prioridad y sigue abierta
        if ($esNueva || ($tipoOPrioridadCambio && $task->estado !== 'completada')) {
            $task->aplicarSla();
        }

        // Manejo de transiciones de fecha segun estado
        if ($this->estado === 'en_progreso' && ! $task->fecha_inicio_real) {
            $task->fecha_inicio_real = now();
        }

        if ($this->estado === 'completada') {
            if (! $task->fecha_completada) {
                $task->completar();
            }
        } else {
            // Si se reabre, limpiar cierre
            $task->fecha_completada = null;
            $task->cumplida_a_tiempo = null;
            $task->save();
        }

        // Bitacora
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'accion'  => $esNueva ? 'creacion' : 'actualizacion',
            'detalle' => $esNueva
                ? 'Tarea creada'
                : "Actualizada (estado {$estadoAnterior} → {$this->estado})",
        ]);

        $task->proyecto?->recalcularProgreso();

        session()->flash('ok', $esNueva ? 'Tarea creada correctamente.' : 'Tarea actualizada.');

        return $this->redirect(route('tareas'), navigate: true);
    }

    public function render()
    {
        return view('livewire.tareas.form-tarea', [
            'proyectos' => Project::orderBy('nombre')->get(),
            'empleados' => User::where('activo', true)->orderBy('name')->get(),
            'bitacora'  => $this->task?->actividades()->with('user')->limit(20)->get() ?? collect(),
        ]);
    }
}
