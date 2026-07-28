<?php

namespace App\Livewire\Tareas;

use App\Domain\Organization\Models\SubDepartment;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Services\CapacidadService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FormTarea extends Component
{
    public ?Task $task = null;

    public bool $enModal = false;

    /**
     * Nombre Livewire del padre que alojó el modal (p. ej. tareas.lista-tareas
     * o proyectos.tablero-proyecto). Necesario para ->to() tras guardar/cancelar
     * (Livewire 3 no propaga bien eventos desde hijos montados dentro de @if).
     */
    public string $padreLivewire = 'tareas.lista-tareas';

    // Proyecto pre-seleccionado (por el padre, tipicamente desde ?project=ID)
    public ?int $project_id = null;

    // Campos del formulario
    public string $titulo = '';

    public string $descripcion = '';

    public string $sub_department_id = '';

    public string $prioridad = 'media';

    public string $estado = 'pendiente';

    public ?int $asignado_id = null;

    public ?string $fechaInicioInput = null;

    public string $tag = '';

    // Modificacion manual de la fecha limite: solo el administrador puede
    // hacerlo, y debe dejar una observacion justificando el cambio.
    public ?string $fechaLimiteInput = null;

    public string $observacionFecha = '';

    public function mount(?Task $task = null, ?int $projectId = null, bool $enModal = false, string $padreLivewire = 'tareas.lista-tareas'): void
    {
        // Crear requiere 'tasks.create'; editar requiere 'tasks.edit'.
        abort_unless(! $task?->exists ? Auth::user()?->puedeCrearTarea() : Auth::user()?->puedeEditarTarea(), 403);

        // Los departamentos son independientes entre si: editar una tarea de
        // otro departamento no es posible aunque se tenga el permiso global
        // 'tasks.edit' (salvo SuperAdmin).
        if ($task?->exists && ! Auth::user()->esSuperAdmin()) {
            abort_unless($task->subDepartamento?->department_id === Auth::user()->departments()->first()?->id, 403);
        }

        $this->enModal = $enModal;
        $this->padreLivewire = $padreLivewire;

        if ($task?->exists) {
            $this->task = $task;
            $this->project_id = $task->project_id;
            $this->titulo = $task->titulo;
            $this->descripcion = $task->descripcion ?? '';
            $this->sub_department_id = (string) $task->sub_department_id;
            $this->prioridad = $task->prioridad;
            $this->estado = $task->estado;
            $this->asignado_id = $task->asignado_id;
            $this->fechaInicioInput = $task->fecha_inicio?->format('Y-m-d');
            $this->fechaLimiteInput = $task->fecha_limite?->format('Y-m-d\TH:i');
            $this->tag = $task->tag ?? '';
        } elseif ($projectId) {
            $this->project_id = $projectId;
        }

        // Si se crea desde un proyecto, heredar su subdepartamento por defecto
        if (! $task?->exists && $this->project_id) {
            $proyecto = Project::find($this->project_id);
            if ($proyecto) {
                $this->sub_department_id = (string) $proyecto->sub_department_id;
            }
        }

        // El evaluador solo puede crear tareas con el tag "certificacion",
        // y el campo queda bloqueado (no editable) en el formulario.
        if (! $task?->exists && $this->esEvaluadorNoAdmin()) {
            $this->tag = 'certificacion';
        }
    }

    protected function esEvaluadorNoAdmin(): bool
    {
        $u = Auth::user();

        return $u && $u->esEvaluador() && ! $u->esAdmin();
    }

    public function getTagBloqueadoProperty(): bool
    {
        return ! $this->task && $this->esEvaluadorNoAdmin();
    }

    /**
     * Si el asignado actual ya no pertenece al equipo del proyecto elegido,
     * limpiar la seleccion para forzar elegir a alguien del equipo.
     * (Tambien se limpia en el cliente al cambiar proyecto; esto cubre el submit.)
     */
    public function updatedProjectId(): void
    {
        $disponibles = $this->empleadosDisponibles()->pluck('id')->all();

        if ($this->asignado_id && ! in_array((int) $this->asignado_id, array_map('intval', $disponibles), true)) {
            $this->asignado_id = null;
        }
    }

    /**
     * Empleados a los que se puede asignar la tarea. Si hay un proyecto con
     * equipo definido, se limita a ese equipo; de lo contrario, todos.
     */
    public function empleadosDisponibles()
    {
        if ($this->project_id) {
            $proyecto = Project::with('equipo.subDepartments')->find($this->project_id);
            if ($proyecto && $proyecto->equipo->isNotEmpty()) {
                return $proyecto->equipo->sortBy('name')->values();
            }
        }

        $user = Auth::user();
        $miDepartamentoId = $user->departments()->first()?->id;

        return User::where('activo', true)->with('subDepartments')
            ->when(! $user->esSuperAdmin(), fn ($q) => $q->whereHas('departments', fn ($q2) => $q2->where('departments.id', $miDepartamentoId)))
            ->orderBy('name')->get();
    }

    /**
     * Mapa proyecto → integrantes (y clave '' = pool general) para filtrar el
     * select de asignados en el cliente sin round-trips Livewire.
     *
     * @return array<string, list<array{id: int, label: string}>>
     */
    protected function equiposPorProyectoParaVista($proyectos, CapacidadService $servicio): array
    {
        $user = Auth::user();
        $miDepartamentoId = $user->departments()->first()?->id;

        $formatear = function ($usuarios) use ($servicio) {
            return $usuarios->sortBy('name')->values()->map(function (User $e) use ($servicio) {
                $carga = $servicio->cargaSemanaActual($e);

                return [
                    'id' => $e->id,
                    'label' => $e->name.' ('.$e->subDepartamentoNombre().') · '.$carga['porcentaje'].'% carga',
                ];
            })->all();
        };

        $generales = User::where('activo', true)->with('subDepartments')
            ->when(! $user->esSuperAdmin(), fn ($q) => $q->whereHas('departments', fn ($q2) => $q2->where('departments.id', $miDepartamentoId)))
            ->orderBy('name')->get();

        $mapa = ['' => $formatear($generales)];

        foreach ($proyectos as $proyecto) {
            $proyecto->loadMissing('equipo.subDepartments');
            $equipo = $proyecto->equipo;
            $mapa[(string) $proyecto->id] = $equipo->isNotEmpty()
                ? $formatear($equipo)
                : [];
        }

        return $mapa;
    }

    protected function rules(): array
    {
        return [
            'titulo' => 'required|string|min:3|max:255',
            'descripcion' => 'nullable|string',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'estado' => 'required|in:pendiente,en_progreso,en_revision,completada,cancelada,rechazada',
            'project_id' => 'nullable|exists:projects,id',
            'asignado_id' => 'nullable|exists:users,id',
            'fechaInicioInput' => 'nullable|date',
            'tag' => 'nullable|string|max:40',
        ];
    }

    /** Solo el administrador puede modificar manualmente la fecha limite. */
    public function getEsAdminProperty(): bool
    {
        return Auth::user()?->esAdmin() ?? false;
    }

    /** El coordinador solo puede eliminar si la tarea no tiene subtareas; el admin siempre. */
    public function getPuedeEliminarProperty(): bool
    {
        return $this->task && (Auth::user()?->puedeEliminarTarea($this->task) ?? false);
    }

    /**
     * Previsualiza la carga resultante del colaborador seleccionado antes de
     * guardar, usando las horas estimadas actuales de la tarea (si las hay).
     */
    public function getCargaPreviaProperty(): ?array
    {
        if (! $this->asignado_id || ! $this->task || ! $this->task->horas_estimadas) {
            return null;
        }

        $colaborador = User::find($this->asignado_id);
        if (! $colaborador) {
            return null;
        }

        $inicio = $this->fechaInicioInput ? Carbon::parse($this->fechaInicioInput) : $this->task->fecha_inicio;

        return app(CapacidadService::class)->validarAsignacion(
            $colaborador,
            (float) $this->task->horas_estimadas,
            $inicio,
            $this->task->id,
        );
    }

    public function eliminar()
    {
        abort_unless($this->task && Auth::user()?->puedeEliminarTarea($this->task), 403);

        $nombre = $this->task->titulo;
        $proyectoId = $this->task->project_id;
        $proyecto = $this->task->proyecto;

        AuditLog::registrar('tarea_eliminada', null, "Tarea eliminada: {$nombre}");
        $this->task->delete();
        $proyecto?->recalcularProgreso();

        if ($this->enModal) {
            $this->avisarPadre('Tarea eliminada.');

            return;
        }

        session()->flash('ok', 'Tarea eliminada.');
        $this->dispatch('app-toast', type: 'success', message: 'Tarea eliminada.');

        return $proyectoId
            ? $this->redirect(route('proyectos.ver', $proyectoId, absolute: false), navigate: true)
            : $this->redirect(route('tareas', absolute: false), navigate: true);
    }

    /** True si el admin edito el valor de la fecha limite en el formulario. */
    public function getFechaLimiteCambiadaProperty(): bool
    {
        if (! $this->task) {
            return false;
        }

        $actual = $this->task->fecha_limite?->format('Y-m-d\TH:i') ?? '';

        return ($this->fechaLimiteInput ?? '') !== $actual;
    }

    public function save()
    {
        // Usar exists: Livewire puede hidratar un Task vacio (truthy) al crear.
        $esNueva = ! $this->task?->exists;

        // Re-chequeo defensivo de permisos en el servidor (no solo en mount).
        abort_unless($esNueva ? Auth::user()?->puedeCrearTarea() : Auth::user()?->puedeEditarTarea(), 403);

        $reglas = $this->rules();

        // Solo al editar (no al crear) y solo el admin puede tocar la fecha
        // limite manualmente; si la cambia, la observacion es obligatoria.
        $puedeEditarFecha = $this->task && $this->esAdmin;
        if ($puedeEditarFecha) {
            $reglas['fechaLimiteInput'] = 'nullable|date';
            if ($this->fechaLimiteCambiada) {
                $reglas['observacionFecha'] = 'required|string|min:5|max:500';
            }
        }

        $this->validate($reglas, [], [
            'fechaLimiteInput' => 'fecha límite',
            'observacionFecha' => 'observación',
        ]);

        // Se evalua aqui, antes de tocar $this->task, para comparar contra
        // el valor original en base de datos.
        $fechaCambioManual = $puedeEditarFecha && $this->fechaLimiteCambiada;

        // El asignado debe pertenecer al equipo del proyecto (si el proyecto tiene equipo)
        if ($this->asignado_id) {
            $disponibles = $this->empleadosDisponibles()->pluck('id')->all();
            if (! in_array($this->asignado_id, $disponibles, true)) {
                $this->addError('asignado_id', 'La persona seleccionada no pertenece al equipo del proyecto.');

                return;
            }
        }

        $task = ($this->task?->exists ? $this->task : null) ?? new Task([
            'creado_por' => Auth::id(),
            'fecha_asignacion' => now(),
        ]);

        // El tag del evaluador es obligatorio y esta bloqueado en la UI; se
        // fuerza tambien aqui para que no se pueda saltar la restriccion.
        if ($esNueva && $this->esEvaluadorNoAdmin()) {
            $this->tag = 'certificacion';
        }

        // Validacion de capacidad por disponibilidad semanal (dia a dia),
        // independiente del SLA / fecha limite.
        $capacidad = app(CapacidadService::class);
        $planDisponibilidad = [];
        if ($this->asignado_id && $task->horas_estimadas > 0) {
            $inicio = $this->fechaInicioInput ? Carbon::parse($this->fechaInicioInput) : $task->fecha_inicio;
            $colaborador = User::find($this->asignado_id);
            $resultado = $capacidad->validarAsignacion(
                $colaborador,
                (float) $task->horas_estimadas,
                $inicio,
                $task->id,
            );

            if (! $resultado['ok']) {
                $this->addError('asignado_id', $resultado['mensaje']);
                if ($task->exists) {
                    TaskActivity::create([
                        'task_id' => $task->id,
                        'user_id' => Auth::id(),
                        'accion' => 'bloqueo_capacidad',
                        'detalle' => $resultado['mensaje'],
                    ]);
                }

                return;
            }

            $planDisponibilidad = $resultado['plan'] ?? [];
        }

        // Valores previos para la trazabilidad granular
        $prev = [
            'estado' => $task->estado,
            'asignado_id' => $task->asignado_id,
            'prioridad' => $task->prioridad,
            'fecha_limite' => $task->fecha_limite,
        ];

        $task->fill([
            'project_id' => $this->project_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'sub_department_id' => $this->sub_department_id,
            'prioridad' => $this->prioridad,
            'estado' => $this->estado,
            'asignado_id' => $this->asignado_id,
            'fecha_inicio' => $this->fechaInicioInput ?: null,
            'tag' => $this->tag ?: null,
        ]);

        // Vencimiento = ultimo dia del plan de disponibilidad (sin SLA).
        if ($this->asignado_id && $task->horas_estimadas > 0 && ! $this->fechaLimiteInput) {
            $task->fecha_limite = $capacidad->fechaFinPlan($planDisponibilidad, $task->fecha_limite);
            $task->sla_horas = null;
        }

        // Manejo de transiciones de fecha segun estado
        if ($this->estado === 'en_progreso' && ! $task->fecha_inicio_real) {
            $task->fecha_inicio_real = now();
        }

        if ($this->estado === 'completada') {
            if (! $task->fecha_completada) {
                // completar() persiste estado + cierre + el resto de dirty attrs
                // (titulo, asignado_id, etc.) rellenados arriba con fill().
                $task->completar();
            } else {
                // Ya estaba cerrada: hay que guardar igual los demas campos
                // (p. ej. reasignacion); sin este save el fill queda solo en memoria.
                $task->save();
            }
        } else {
            // Si se reabre, limpiar cierre
            $task->fecha_completada = null;
            $task->cumplida_a_tiempo = null;
            $task->save();
        }

        // Override manual de la fecha limite (solo admin, solo al editar).
        // Se aplica despues de las transiciones de estado para que tenga
        // siempre la ultima palabra sobre el valor final.
        if ($fechaCambioManual) {
            $task->fecha_limite = $this->fechaLimiteInput ? Carbon::parse($this->fechaLimiteInput) : null;

            // Si la tarea ya esta cerrada, reevaluar el cumplimiento del SLA
            // contra la nueva fecha limite.
            if ($task->fecha_completada) {
                $task->cumplida_a_tiempo = $task->fecha_limite
                    ? $task->fecha_completada->lessThanOrEqualTo($task->fecha_limite)
                    : true;
            }

            $task->save();
        }

        // Ubicar la tarea en el tablero Kanban segun su estado final, para que
        // aparezca automaticamente al crearla o al cambiar de estado.
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

        // Bitacora granular (no destructiva)
        if ($esNueva) {
            $this->registrar($task, 'creacion', 'Tarea creada');
            if ($task->asignado_id) {
                $this->registrar($task, 'reasignacion', 'Asignada a '.(User::find($task->asignado_id)?->name ?? '—'));
            }
        } else {
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
                    $detalle .= '. Motivo: '.trim($this->observacionFecha);
                }
                $this->registrar($task, 'cambio_fecha_limite', $detalle);
            }
        }

        $task->proyecto?->recalcularProgreso();

        $mensaje = $esNueva ? 'Tarea creada correctamente.' : 'Tarea actualizada.';

        if ($this->enModal) {
            $this->avisarPadre($mensaje);

            return;
        }

        session()->flash('ok', $mensaje);
        $this->dispatch('app-toast', type: 'success', message: $mensaje);

        // absolute: false evita saltar a APP_URL de produccion cuando se
        // desarrolla en localhost (wire:navigate usaria el host de APP_URL).
        if ($task->project_id) {
            return $this->redirect(route('proyectos.tablero', $task->project_id, absolute: false), navigate: true);
        }

        return $this->redirect(route('tareas', absolute: false), navigate: true);
    }

    /**
     * ->to() en vez de dispatch() simple: este componente esta montado
     * dinamicamente dentro del modal del padre, y tras una accion Livewire
     * puede dejar su propio elemento en un estado que ya no propaga eventos
     * de forma confiable (bug de Livewire 3 con componentes anidados via @if
     * - el modal se quedaba abierto y no salia el toast). ->to() ubica al
     * padre por nombre y dispara el evento directo en su elemento, sin
     * depender del DOM de este hijo.
     */
    public function cancelar(): void
    {
        $this->avisarPadre();
    }

    protected function avisarPadre(?string $mensaje = null): void
    {
        $this->dispatch('cerrar-modal-tarea', mensaje: $mensaje)->to($this->padreLivewire);
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
        $servicio = app(CapacidadService::class);
        $user = Auth::user();
        $miDepartamentoId = $user->departments()->first()?->id;

        $proyectos = Project::visiblesPara($user)->with('equipo.subDepartments')->orderBy('nombre')->get();
        $subDepartamentos = SubDepartment::where('activo', true)
            ->when(! $user->esSuperAdmin(), fn ($q) => $q->where('department_id', $miDepartamentoId))
            ->orderBy('nombre')->get();

        return view('livewire.tareas.form-tarea', [
            'proyectos' => $proyectos,
            'equiposPorProyecto' => $this->equiposPorProyectoParaVista($proyectos, $servicio),
            'subDepartamentos' => $subDepartamentos,
            'bitacora' => $this->task?->actividades()->with('user')->limit(20)->get() ?? collect(),
            'esAdmin' => $this->esAdmin,
            'puedeEliminar' => $this->puedeEliminar,
            'cargaPrevia' => $this->cargaPrevia,
            'fechaLimiteCambiada' => $this->fechaLimiteCambiada,
            'tagBloqueado' => $this->tagBloqueado,
        ]);
    }
}
