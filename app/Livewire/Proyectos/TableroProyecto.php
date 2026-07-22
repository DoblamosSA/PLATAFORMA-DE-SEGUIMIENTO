<?php

namespace App\Livewire\Proyectos;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Services\CapacidadService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tablero Kanban de un proyecto. Administra columnas (personalizables),
 * el arrastre de cards entre columnas y el foro de comentarios/trazabilidad
 * de cada tarea. La logica de SLA se conserva: al mover una card, la tarea
 * adopta el estado canonico de la columna destino.
 */
#[Layout('layouts.app')]
class TableroProyecto extends Component
{
    /** Estados canonicos del sistema (columna -> estado). */
    public const ESTADOS = ['pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada', 'rechazada'];

    public const ESTADOS_LABEL = [
        'pendiente' => 'Pendiente',
        'en_progreso' => 'En progreso',
        'en_revision' => 'En revision',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
        'rechazada' => 'Rechazada',
    ];

    public Project $project;

    /** Tarea abierta en el panel de detalle (null = cerrado). */
    public ?int $tareaSeleccionadaId = null;

    // Alta de columna
    public string $nuevaColumnaNombre = '';

    public string $nuevaColumnaEstado = 'pendiente';

    // Foro de la tarea seleccionada
    public string $nuevoComentario = '';

    // Edicion en linea de la tarea seleccionada (dentro del panel)
    public bool $editando = false;

    public string $edTitulo = '';

    public string $edDescripcion = '';

    public string $edTipo = 'soporte';

    public string $edPrioridad = 'media';

    public string $edEstado = 'pendiente';

    public ?int $edAsignadoId = null;

    // Modificacion manual de la fecha limite: solo el administrador puede
    // hacerlo, y debe dejar una observacion justificando el cambio.
    public ?string $edFechaLimiteInput = null;

    public string $edObservacionFecha = '';

    // Subtareas de la tarea seleccionada
    public string $nuevaSubtareaTitulo = '';

    public ?string $nuevaSubtareaHoras = null;

    // Rechazo de tarea completada (solo evaluador)
    public bool $rechazando = false;

    public string $motivoRechazo = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->autorizar();
        $this->project->asegurarColumnas();

        // Deep-link (p. ej. al tocar una notificacion push): ?tarea=ID abre
        // el panel de detalle de esa tarea. Si ya no existe, queda el tablero.
        $tareaId = (int) request()->query('tarea');
        if ($tareaId && Task::where('project_id', $project->id)->whereKey($tareaId)->exists()) {
            $this->abrirTarea($tareaId);
        }
    }

    // ---------------------------------------------------------------
    // Autorizacion
    // ---------------------------------------------------------------

    protected function autorizar(): void
    {
        abort_unless($this->project->usuarioPuedeGestionar(Auth::user()), 403);
    }

    // ---------------------------------------------------------------
    // Movimiento de cards (drag & drop)
    // ---------------------------------------------------------------

    /**
     * Mueve una tarea a una columna y persiste el nuevo orden. Actualiza el
     * estado real, registra la actividad y recalcula el progreso.
     *
     * @param  array<int, int|string>  $orden  IDs de tareas en la columna destino, en orden.
     */
    public function moverTarea(int $taskId, int $columnId, array $orden = []): void
    {
        $this->autorizar();

        $task = $this->tarea($taskId);
        $columna = $this->columna($columnId);

        $estadoAnterior = $task->estado;
        $columnaAnterior = $task->board_column_id;

        $task->board_column_id = $columna->id;
        if ($columna->estado !== $estadoAnterior) {
            $this->aplicarCambioEstado($task, $columna->estado);
        }
        $task->save();

        // Persistir el orden dentro de la columna destino
        foreach ($orden as $pos => $id) {
            Task::where('project_id', $this->project->id)
                ->where('id', (int) $id)
                ->update(['posicion' => $pos]);
        }

        // Trazabilidad solo si cambio de columna
        if ($columnaAnterior !== $columna->id) {
            $detalle = "Movida a \"{$columna->nombre}\"";
            if ($columna->estado !== $estadoAnterior) {
                $detalle .= " (estado: {$estadoAnterior} -> {$columna->estado})";
            }
            $this->registrar($task, 'cambio_estado', $detalle);
        }

        $this->project->recalcularProgreso();
        $this->dispatch('tablero-actualizado');
    }

    /**
     * Aplica el estado canonico destino replicando los efectos de negocio
     * existentes (inicio real, cierre y evaluacion de SLA, reapertura).
     */
    protected function aplicarCambioEstado(Task $task, string $nuevoEstado): void
    {
        if ($nuevoEstado === 'en_progreso' && ! $task->fecha_inicio_real) {
            $task->fecha_inicio_real = now();
        }

        if ($nuevoEstado === 'completada') {
            $task->estado = 'completada';
            if (! $task->fecha_completada) {
                $task->fecha_completada = now();
                $task->cumplida_a_tiempo = $task->fecha_limite
                    ? now()->lessThanOrEqualTo($task->fecha_limite)
                    : true;
            }
        } else {
            $task->estado = $nuevoEstado;
            // Reapertura: limpiar el cierre previo
            $task->fecha_completada = null;
            $task->cumplida_a_tiempo = null;
        }
    }

    // ---------------------------------------------------------------
    // Administracion de columnas
    // ---------------------------------------------------------------

    public function crearColumna(): void
    {
        $this->autorizar();

        $this->validate([
            'nuevaColumnaNombre' => 'required|string|min:2|max:40',
            'nuevaColumnaEstado' => 'required|in:'.implode(',', self::ESTADOS),
        ], [], [
            'nuevaColumnaNombre' => 'nombre',
            'nuevaColumnaEstado' => 'estado',
        ]);

        $posicion = (int) $this->project->columnas()->max('posicion') + 1;

        $this->project->columnas()->create([
            'nombre' => trim($this->nuevaColumnaNombre),
            'estado' => $this->nuevaColumnaEstado,
            'posicion' => $posicion,
            'color' => 'indigo',
        ]);

        $this->reset('nuevaColumnaNombre');
        $this->nuevaColumnaEstado = 'pendiente';
        $this->dispatch('columna-creada');
    }

    public function renombrarColumna(int $columnId, string $nombre): void
    {
        $this->autorizar();

        $nombre = trim($nombre);
        if (mb_strlen($nombre) < 2) {
            $this->addError('columna', 'El nombre de la columna es muy corto.');

            return;
        }

        $this->columna($columnId)->update(['nombre' => mb_substr($nombre, 0, 40)]);
    }

    /**
     * @param  array<int, int|string>  $orden  IDs de columnas en el nuevo orden.
     */
    public function reordenarColumnas(array $orden): void
    {
        $this->autorizar();

        foreach ($orden as $pos => $id) {
            BoardColumn::where('project_id', $this->project->id)
                ->where('id', (int) $id)
                ->update(['posicion' => $pos]);
        }
    }

    /**
     * Elimina una columna solo si esta vacia. No se permite eliminar una
     * columna con tareas (deben moverse antes, arrastrandolas).
     */
    public function eliminarColumna(int $columnId): void
    {
        $this->autorizar();

        if ($this->project->columnas()->count() <= 1) {
            $this->addError('columna', 'El tablero debe conservar al menos una columna.');

            return;
        }

        $columna = $this->columna($columnId);

        if ($columna->tareas()->count() > 0) {
            $this->addError('columna', "No puedes eliminar \"{$columna->nombre}\" porque tiene tareas. Muevelas a otra columna primero.");

            return;
        }

        $columna->delete();
        $this->dispatch('columna-eliminada');
    }

    // ---------------------------------------------------------------
    // Panel de detalle + foro de comentarios
    // ---------------------------------------------------------------

    public function abrirTarea(int $taskId): void
    {
        $this->tarea($taskId); // valida pertenencia al proyecto
        $this->tareaSeleccionadaId = $taskId;
        $this->editando = false;
        $this->resetErrorBag();
    }

    public function cerrarTarea(): void
    {
        $this->tareaSeleccionadaId = null;
        $this->editando = false;
        $this->reset('nuevoComentario');
        $this->resetErrorBag();
    }

    // ---------------------------------------------------------------
    // Edicion en linea (sin salir del panel)
    // ---------------------------------------------------------------

    public function iniciarEdicion(): void
    {
        $this->autorizar();

        $task = $this->tarea($this->tareaSeleccionadaId);

        $this->edTitulo = $task->titulo;
        $this->edDescripcion = $task->descripcion ?? '';
        $this->edTipo = $task->tipo;
        $this->edPrioridad = $task->prioridad;
        $this->edEstado = $task->estado;
        $this->edAsignadoId = $task->asignado_id;
        $this->edFechaLimiteInput = $task->fecha_limite?->format('Y-m-d\TH:i');
        $this->edObservacionFecha = '';

        $this->editando = true;
        $this->resetErrorBag();
    }

    public function cancelarEdicion(): void
    {
        $this->editando = false;
        $this->resetErrorBag();
    }

    /** Solo el administrador puede modificar manualmente la fecha limite. */
    public function getEsAdminProperty(): bool
    {
        return Auth::user()?->esAdmin() ?? false;
    }

    /** True si el admin edito el valor de la fecha limite en el panel. */
    public function getEdFechaLimiteCambiadaProperty(): bool
    {
        if (! $this->tareaSeleccionadaId) {
            return false;
        }

        $actual = $this->tarea($this->tareaSeleccionadaId)->fecha_limite?->format('Y-m-d\TH:i') ?? '';

        return ($this->edFechaLimiteInput ?? '') !== $actual;
    }

    /** Previsualiza la carga resultante del colaborador antes de guardar la edicion. */
    public function getEdCargaPreviaProperty(): ?array
    {
        if (! $this->edAsignadoId || ! $this->tareaSeleccionadaId) {
            return null;
        }

        $task = $this->tarea($this->tareaSeleccionadaId);
        if (! $task->horas_estimadas) {
            return null;
        }

        $colaborador = User::find($this->edAsignadoId);
        if (! $colaborador) {
            return null;
        }

        $limite = $this->edFechaLimiteInput ? Carbon::parse($this->edFechaLimiteInput) : $task->fecha_limite;

        return app(CapacidadService::class)->validarAsignacion(
            $colaborador,
            (float) $task->horas_estimadas,
            $task->fecha_inicio,
            $limite,
            $task->id,
        );
    }

    /** Solo el evaluador (o admin) puede rechazar, y solo una tarea completada. */
    public function getPuedeRechazarProperty(): bool
    {
        if (! $this->tareaSeleccionadaId) {
            return false;
        }

        $task = $this->tarea($this->tareaSeleccionadaId);

        return $task->estado === 'completada' && (Auth::user()?->puedeRechazarTarea() ?? false);
    }

    /**
     * Guarda los cambios de la tarea reutilizando la logica de negocio del
     * formulario: recalculo de SLA, transiciones de estado, ubicacion en el
     * tablero segun el estado final y trazabilidad granular (no destructiva).
     */
    public function guardarEdicion(): void
    {
        $this->autorizar();

        $reglas = [
            'edTitulo' => 'required|string|min:3|max:255',
            'edDescripcion' => 'nullable|string',
            'edTipo' => 'required|in:software,soporte,infraestructura',
            'edPrioridad' => 'required|in:baja,media,alta,critica',
            'edEstado' => 'required|in:'.implode(',', self::ESTADOS),
            'edAsignadoId' => 'nullable|exists:users,id',
        ];

        // Solo el admin puede tocar la fecha limite; si la cambia, la
        // observacion es obligatoria.
        if ($this->esAdmin) {
            $reglas['edFechaLimiteInput'] = 'nullable|date';
            if ($this->edFechaLimiteCambiada) {
                $reglas['edObservacionFecha'] = 'required|string|min:5|max:500';
            }
        }

        $this->validate($reglas, [], [
            'edTitulo' => 'título',
            'edPrioridad' => 'prioridad',
            'edEstado' => 'estado',
            'edAsignadoId' => 'asignado',
            'edFechaLimiteInput' => 'fecha límite',
            'edObservacionFecha' => 'observación',
        ]);

        // Se evalua antes de tocar la tarea, para comparar contra el valor
        // original en base de datos.
        $fechaCambioManual = $this->esAdmin && $this->edFechaLimiteCambiada;

        $task = $this->tarea($this->tareaSeleccionadaId);

        // El asignado debe pertenecer al equipo del proyecto (si tiene equipo)
        if ($this->edAsignadoId) {
            $disponibles = $this->empleadosDisponibles()->pluck('id')->all();
            if (! in_array($this->edAsignadoId, $disponibles, true)) {
                $this->addError('edAsignadoId', 'La persona seleccionada no pertenece al equipo del proyecto.');

                return;
            }
        }

        // Bloquea la reasignacion si supera la capacidad del colaborador en
        // el periodo de la tarea (solo si ya tiene horas estimadas por sus subtareas).
        if ($this->edAsignadoId && $task->horas_estimadas > 0) {
            $limite = $this->edFechaLimiteInput ? Carbon::parse($this->edFechaLimiteInput) : $task->fecha_limite;
            $resultado = app(CapacidadService::class)->validarAsignacion(
                User::find($this->edAsignadoId),
                (float) $task->horas_estimadas,
                $task->fecha_inicio,
                $limite,
                $task->id,
            );

            if (! $resultado['ok']) {
                $this->addError('edAsignadoId', $resultado['mensaje']);
                $this->registrar($task, 'bloqueo_capacidad', $resultado['mensaje']);

                return;
            }
        }

        $prev = [
            'estado' => $task->estado,
            'asignado_id' => $task->asignado_id,
            'prioridad' => $task->prioridad,
            'fecha_limite' => $task->fecha_limite,
        ];
        $tipoOPrioridadCambio = $task->tipo !== $this->edTipo || $task->prioridad !== $this->edPrioridad;

        $task->fill([
            'titulo' => $this->edTitulo,
            'descripcion' => $this->edDescripcion,
            'tipo' => $this->edTipo,
            'prioridad' => $this->edPrioridad,
            'estado' => $this->edEstado,
            'asignado_id' => $this->edAsignadoId,
        ]);

        if ($tipoOPrioridadCambio && $task->estado !== 'completada') {
            $task->aplicarSla();
        }

        if ($this->edEstado === 'en_progreso' && ! $task->fecha_inicio_real) {
            $task->fecha_inicio_real = now();
        }

        if ($this->edEstado === 'completada') {
            if (! $task->fecha_completada) {
                $task->completar();
            }
        } else {
            $task->fecha_completada = null;
            $task->cumplida_a_tiempo = null;
            $task->save();
        }

        // Override manual de la fecha limite (solo admin). Se aplica despues
        // del SLA/transiciones de estado para que tenga la ultima palabra.
        if ($fechaCambioManual) {
            $task->fecha_limite = $this->edFechaLimiteInput ? Carbon::parse($this->edFechaLimiteInput) : null;

            if ($task->fecha_completada) {
                $task->cumplida_a_tiempo = $task->fecha_limite
                    ? $task->fecha_completada->lessThanOrEqualTo($task->fecha_limite)
                    : true;
            }

            $task->save();
        }

        // Ubicar la card en la columna que corresponde al estado final
        $this->project->asegurarColumnas();
        $actual = $task->columna;
        if (! $actual || $actual->estado !== $task->estado) {
            if ($columna = $this->project->columnaParaEstado($task->estado)) {
                $task->board_column_id = $columna->id;
                $task->posicion = (int) Task::where('board_column_id', $columna->id)->max('posicion') + 1;
                $task->save();
            }
        }

        // Trazabilidad granular
        if ($prev['estado'] !== $task->estado) {
            $this->registrar($task, 'cambio_estado', "Estado: {$prev['estado']} → {$task->estado}");
        }
        if ($prev['asignado_id'] !== $task->asignado_id) {
            $this->registrar($task, 'reasignacion', 'Reasignada a '.(User::find($task->asignado_id)?->name ?? 'Sin asignar'));
        }
        if ($prev['prioridad'] !== $task->prioridad) {
            $this->registrar($task, 'cambio_prioridad', "Prioridad: {$prev['prioridad']} → {$task->prioridad}");
        }
        $limAntes = optional($prev['fecha_limite'])->format('d/m/Y H:i');
        $limAhora = optional($task->fecha_limite)->format('d/m/Y H:i');
        if ($limAntes !== $limAhora) {
            $detalle = 'Fecha límite: '.($limAntes ?? '—').' → '.($limAhora ?? '—');
            if ($fechaCambioManual) {
                $detalle .= '. Motivo: '.trim($this->edObservacionFecha);
            }
            $this->registrar($task, 'cambio_fecha_limite', $detalle);
        }

        $this->project->recalcularProgreso();
        $this->editando = false;
        $this->dispatch('tablero-actualizado');
    }

    /**
     * Empleados asignables: el equipo del proyecto, o todos los activos si
     * el proyecto aun no tiene equipo definido.
     */
    protected function empleadosDisponibles()
    {
        $equipo = $this->project->equipo()->orderBy('name')->get();

        return $equipo->isNotEmpty()
            ? $equipo
            : User::where('activo', true)->orderBy('name')->get();
    }

    public function comentar(): void
    {
        $this->autorizar();
        abort_unless(Auth::user()?->puedeCrearComentario(), 403);

        $this->validate(
            ['nuevoComentario' => 'required|string|min:1|max:2000'],
            [],
            ['nuevoComentario' => 'comentario']
        );

        $task = $this->tarea($this->tareaSeleccionadaId);
        $this->registrar($task, 'comentario', trim($this->nuevoComentario));
        $this->reset('nuevoComentario');
    }

    /** Solo el administrador puede eliminar comentarios. */
    public function eliminarComentario(int $activityId): void
    {
        $this->autorizar();
        abort_unless(Auth::user()?->puedeEliminarComentario(), 403);

        TaskActivity::where('task_id', $this->tareaSeleccionadaId)
            ->where('accion', 'comentario')
            ->findOrFail($activityId)
            ->delete();
    }

    // ---------------------------------------------------------------
    // Subtareas (desglose de horas)
    // ---------------------------------------------------------------

    /**
     * Agrega una subtarea con su titulo y horas estimadas. Las horas de la
     * tarea principal se recalculan automaticamente como la suma de todas
     * sus subtareas.
     */
    public function agregarSubtarea(): void
    {
        $this->autorizar();
        abort_unless(Auth::user()?->puedeCrearSubtarea(), 403);

        $this->validate([
            'nuevaSubtareaTitulo' => 'required|string|min:2|max:150',
            'nuevaSubtareaHoras' => 'required|numeric|min:0.5|max:999',
        ], [], [
            'nuevaSubtareaTitulo' => 'título',
            'nuevaSubtareaHoras' => 'horas',
        ]);

        $task = $this->tarea($this->tareaSeleccionadaId);

        // Si la tarea ya esta asignada, valida que la nueva carga (horas ya
        // estimadas + esta subtarea) no supere la capacidad del colaborador
        // en el periodo de la tarea. Bloquea la creacion si la excede.
        if ($task->asignado_id && $task->fecha_limite) {
            $horasProyectadas = (float) ($task->horas_estimadas ?? 0) + (float) $this->nuevaSubtareaHoras;
            $resultado = app(CapacidadService::class)->validarAsignacion(
                $task->asignado,
                $horasProyectadas,
                $task->fecha_inicio,
                $task->fecha_limite,
                $task->id,
            );

            if (! $resultado['ok']) {
                $this->addError('nuevaSubtareaHoras', $resultado['mensaje']);
                $this->registrar($task, 'bloqueo_capacidad', $resultado['mensaje']);

                return;
            }
        }

        $task->subtareas()->create([
            'titulo' => trim($this->nuevaSubtareaTitulo),
            'horas' => (float) $this->nuevaSubtareaHoras,
            'creado_por' => Auth::id(),
        ]);

        $task->recalcularHoras();

        $horasSubtarea = rtrim(rtrim(number_format((float) $this->nuevaSubtareaHoras, 2), '0'), '.');
        $horasTotal = rtrim(rtrim(number_format((float) $task->fresh()->horas_estimadas, 2), '0'), '.');

        $this->registrar(
            $task,
            'subtarea',
            "Subtarea agregada: \"{$this->nuevaSubtareaTitulo}\" ({$horasSubtarea}h) · total {$horasTotal}h"
        );

        $this->reset('nuevaSubtareaTitulo', 'nuevaSubtareaHoras');
    }

    /** Solo el administrador puede eliminar subtareas. */
    public function eliminarSubtarea(int $subtareaId): void
    {
        $this->autorizar();
        abort_unless(Auth::user()?->puedeEliminarSubtarea(), 403);

        $task = $this->tarea($this->tareaSeleccionadaId);
        $subtarea = $task->subtareas()->findOrFail($subtareaId);
        $titulo = $subtarea->titulo;
        $subtarea->delete();
        $task->recalcularHoras();

        $this->registrar($task, 'subtarea', "Subtarea eliminada: \"{$titulo}\"");
    }

    // ---------------------------------------------------------------
    // Rechazo de tarea completada (evaluador)
    // ---------------------------------------------------------------

    public function iniciarRechazo(): void
    {
        $this->autorizar();
        abort_unless(Auth::user()?->puedeRechazarTarea(), 403);

        $this->rechazando = true;
        $this->motivoRechazo = '';
        $this->resetErrorBag();
    }

    public function cancelarRechazo(): void
    {
        $this->rechazando = false;
        $this->resetErrorBag();
    }

    public function confirmarRechazo(): void
    {
        $this->autorizar();
        abort_unless(Auth::user()?->puedeRechazarTarea(), 403);

        $task = $this->tarea($this->tareaSeleccionadaId);
        abort_unless($task->estado === 'completada', 422);

        $this->validate(
            ['motivoRechazo' => 'required|string|min:5|max:500'],
            [],
            ['motivoRechazo' => 'motivo'],
        );

        $task->rechazar();
        $this->registrar($task, 'rechazo', 'Tarea rechazada por el evaluador. Motivo: '.trim($this->motivoRechazo));

        $this->project->asegurarColumnas();
        if ($columna = $this->project->columnaParaEstado('en_revision')) {
            $task->board_column_id = $columna->id;
            $task->save();
        }

        $this->project->recalcularProgreso();
        $this->rechazando = false;
        $this->reset('motivoRechazo');
        $this->dispatch('tablero-actualizado');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function tarea(int $id): Task
    {
        return Task::where('project_id', $this->project->id)->findOrFail($id);
    }

    protected function columna(int $id): BoardColumn
    {
        return BoardColumn::where('project_id', $this->project->id)->findOrFail($id);
    }

    protected function registrar(Task $task, string $accion, string $detalle): void
    {
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'accion' => $accion,
            'detalle' => $detalle,
        ]);
    }

    public function render()
    {
        $columnas = $this->project->columnas()
            ->with(['tareas' => fn ($q) => $q->with('asignado')->withCount('comentarios')->orderBy('posicion')])
            ->get();

        $tareaSeleccionada = $this->tareaSeleccionadaId
            ? Task::with(['asignado', 'proyecto', 'columna', 'actividades.user', 'subtareas'])->find($this->tareaSeleccionadaId)
            : null;

        $servicio = app(CapacidadService::class);
        $empleados = $this->empleadosDisponibles();
        $empleados->each(fn (User $e) => $e->setAttribute('carga', $servicio->cargaSemanaActual($e)));

        return view('livewire.proyectos.tablero-proyecto', [
            'columnas' => $columnas,
            'metricas' => $this->project->metricasCumplimiento(),
            'tareaSeleccionada' => $tareaSeleccionada,
            'empleados' => $empleados,
            'estadosLabel' => self::ESTADOS_LABEL,
            'puedeCrearSubtarea' => Auth::user()?->puedeCrearSubtarea() ?? false,
            'puedeEliminarSubtarea' => Auth::user()?->puedeEliminarSubtarea() ?? false,
            'puedeCrearComentario' => Auth::user()?->puedeCrearComentario() ?? false,
            'puedeEliminarComentario' => Auth::user()?->puedeEliminarComentario() ?? false,
            'esAdmin' => $this->esAdmin,
            'edCargaPrevia' => $this->edCargaPrevia,
            'edFechaLimiteCambiada' => $this->edFechaLimiteCambiada,
            'puedeRechazar' => $this->puedeRechazar,
        ]);
    }
}
