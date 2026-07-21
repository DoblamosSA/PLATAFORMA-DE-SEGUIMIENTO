<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <a href="{{ route('proyectos.ver', $project) }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                <x-icon name="arrow-left" class="w-4 h-4" /> {{ $project->nombre }}
            </a>
            <div class="mt-2 flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-sky-600 text-white shadow-lg shadow-blue-500/30">
                    <x-icon name="tasks" class="w-6 h-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">Administrar tareas</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Tablero Kanban · arrastra las tarjetas entre columnas</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-4 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-sm shadow-sm dark:shadow-black/20">
                <span class="text-slate-500 dark:text-slate-400">Progreso <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums">{{ $project->progreso }}%</span></span>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <span class="text-slate-500 dark:text-slate-400">Cumpl. <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums">{{ $metricas['cumplimiento'] }}%</span></span>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <span class="{{ $metricas['vencidas'] > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">{{ $metricas['vencidas'] }} venc.</span>
            </div>

            {{-- Nueva columna (desplegable) --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.98] transition">
                    <x-icon name="plus" class="w-4 h-4" /> Nueva columna
                </button>
                <div x-show="open" x-transition.origin.top.right style="display:none;"
                     @columna-creada.window="open = false"
                     class="absolute right-0 z-30 mt-2 w-72 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 shadow-xl dark:shadow-black/50">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Nueva columna</p>
                    <form wire:submit="crearColumna" class="space-y-2">
                        <input type="text" wire:model="nuevaColumnaNombre" placeholder="Nombre de la columna" x-ref="nombreCol"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('nuevaColumnaNombre') <span class="block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                        <select wire:model="nuevaColumnaEstado" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($estadosLabel as $valor => $etiqueta)
                                <option value="{{ $valor }}">Estado: {{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/15 px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/25 active:scale-[0.98] transition">
                            <x-icon name="plus" class="w-4 h-4" /> Agregar columna
                        </button>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">El estado define qué ocurre al mover una tarjeta aquí (SLA, progreso).</p>
                    </form>
                </div>
            </div>

            @if (auth()->user()->puedeCrearTarea())
                <a href="{{ route('tareas.crear', ['project' => $project->id]) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    <x-icon name="plus" class="w-4 h-4" /> Asignar tarea
                </a>
            @endif
        </div>
    </div>

    {{-- Mensajes de validacion de columnas --}}
    @error('columna')
        <div class="rounded-xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-400">{{ $message }}</div>
    @enderror

    @php
        $dot = [
            'slate' => 'bg-slate-400', 'sky' => 'bg-sky-500', 'amber' => 'bg-amber-500',
            'emerald' => 'bg-emerald-500', 'indigo' => 'bg-blue-500', 'rose' => 'bg-rose-500',
            'teal' => 'bg-teal-500', 'cyan' => 'bg-cyan-500',
        ];
    @endphp

    {{-- Tablero --}}
    <div wire:key="board-{{ $project->id }}"
         x-data
         x-init="window.kanbanColumns($el, $wire)"
         class="flex gap-4 overflow-x-auto kanban-scroll pb-4 snap-x items-start">

        @foreach ($columnas as $col)
            <section wire:key="col-{{ $col->id }}"
                     data-column data-column-id="{{ $col->id }}"
                     class="w-80 shrink-0 snap-start flex flex-col rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/60 shadow-sm dark:shadow-black/20">

                {{-- Encabezado de columna --}}
                <div data-column-handle
                     class="flex items-center justify-between gap-2 px-4 py-3 cursor-grab active:cursor-grabbing"
                     x-data="{ editando: false, nombre: @js($col->nombre) }">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dot[$col->color] ?? 'bg-slate-400' }}"></span>
                        <h3 x-show="!editando" @dblclick="editando = true; $nextTick(() => $refs.nombreInput.focus())"
                            class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200" title="Doble clic para renombrar">{{ $col->nombre }}</h3>
                        <input x-show="editando" x-ref="nombreInput" x-model="nombre"
                               @keydown.enter="$wire.renombrarColumna({{ $col->id }}, nombre); editando = false"
                               @keydown.escape="nombre = @js($col->nombre); editando = false"
                               @blur="editando = false"
                               class="w-40 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                               style="display:none;" maxlength="40">
                        <span class="shrink-0 rounded-full bg-white dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 tabular-nums">{{ $col->tareas->count() }}</span>
                    </div>
                    <div class="flex items-center gap-0.5 text-slate-400 dark:text-slate-500">
                        <button @click="editando = true; $nextTick(() => $refs.nombreInput.focus())"
                                title="Renombrar" aria-label="Renombrar columna" class="rounded-md p-1 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 active:scale-95 transition">
                            <x-icon name="report" class="w-4 h-4" />
                        </button>
                        <button wire:click="eliminarColumna({{ $col->id }})"
                                title="{{ $col->tareas->count() > 0 ? 'Mueve las tareas antes de eliminar' : 'Eliminar columna' }}"
                                aria-label="Eliminar columna"
                                @if ($col->tareas->count() > 0) disabled @endif
                                class="rounded-md p-1 transition hover:bg-white dark:hover:bg-slate-800 hover:text-rose-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-slate-400 disabled:hover:bg-transparent disabled:active:scale-100">
                            <x-icon name="close" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Estado asociado --}}
                <div class="px-4 -mt-1 pb-2">
                    <span class="inline-flex items-center gap-1 text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <x-icon name="trend" class="w-3 h-3" /> {{ $estadosLabel[$col->estado] ?? $col->estado }}
                    </span>
                </div>

                {{-- Cards (contenedor arrastrable) --}}
                <div wire:key="cards-{{ $col->id }}" data-column-id="{{ $col->id }}"
                     x-data x-init="window.kanbanSortable($el, $wire)"
                     class="flex-1 space-y-3 px-3 pb-3 min-h-[4rem]">
                    @foreach ($col->tareas as $t)
                        <article wire:key="card-{{ $t->id }}"
                                 data-card data-task-id="{{ $t->id }}"
                                 wire:click="abrirTarea({{ $t->id }})"
                                 class="group cursor-pointer rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 shadow-sm dark:shadow-black/20 transition hover:shadow-md hover:border-blue-200 dark:hover:border-blue-500/50 hover:-translate-y-0.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100 leading-snug group-hover:text-blue-700 dark:group-hover:text-blue-400">{{ $t->titulo }}</p>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <x-badge tipo="prioridad" :valor="$t->prioridad" />
                                <x-badge tipo="estado" :valor="$t->estado" />
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2">
                                {{-- Fecha limite + vencimiento --}}
                                @if ($t->fecha_limite)
                                    <span class="inline-flex items-center gap-1 text-[11px] {{ $t->estaVencida() ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-slate-400 dark:text-slate-500' }}">
                                        <x-icon name="{{ $t->estaVencida() ? 'alert' : 'clock' }}" class="w-3.5 h-3.5" />
                                        {{ $t->fecha_limite->format('d/m H:i') }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-300 dark:text-slate-600">Sin fecha</span>
                                @endif

                                <div class="flex items-center gap-2">
                                    @if ($t->comentarios_count > 0)
                                        <span class="inline-flex items-center gap-0.5 text-[11px] text-slate-400 dark:text-slate-500" title="Comentarios">
                                            <x-icon name="support" class="w-3.5 h-3.5" /> {{ $t->comentarios_count }}
                                        </span>
                                    @endif
                                    @if ($t->asignado)
                                        <x-avatar :usuario="$t->asignado" size="h-6 w-6" text="text-[10px]" />
                                    @else
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 text-[10px] text-slate-400 dark:text-slate-500" title="Sin asignar">—</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

    </div>

    {{-- Panel de detalle de la tarea + foro --}}
    @if ($tareaSeleccionada)
        <div class="fixed inset-0 z-50 flex justify-end">
            <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm" wire:click="cerrarTarea"></div>
            <div x-data x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 class="relative flex h-full w-full max-w-lg flex-col bg-white dark:bg-slate-900 shadow-2xl dark:shadow-black/50">
                {{-- Cabecera --}}
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                            <x-badge tipo="prioridad" :valor="$tareaSeleccionada->prioridad" />
                            <x-badge tipo="estado" :valor="$tareaSeleccionada->estado" />
                            <x-badge tipo="tipo" :valor="$tareaSeleccionada->tipo" />
                            @if ($tareaSeleccionada->tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300">{{ ucfirst($tareaSeleccionada->tag) }}</span>
                            @endif
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 leading-tight">{{ $tareaSeleccionada->titulo }}</h2>
                    </div>
                    <button wire:click="cerrarTarea" aria-label="Cerrar panel de tarea" class="rounded-lg p-1.5 text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 active:scale-95 transition">
                        <x-icon name="close" class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                    @if (! $editando)
                        {{-- Datos (solo lectura) --}}
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Asignado</p>
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $tareaSeleccionada->asignado?->name ?? 'Sin asignar' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Columna</p>
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $tareaSeleccionada->columna?->nombre ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Vencimiento</p>
                                <p class="font-medium {{ $tareaSeleccionada->estaVencida() ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }}">
                                    {{ $tareaSeleccionada->fecha_limite?->format('d/m/Y H:i') ?? '—' }}
                                    @if ($tareaSeleccionada->estaVencida()) · vencida @endif
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">SLA</p>
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $tareaSeleccionada->sla_horas ? $tareaSeleccionada->sla_horas.' h' : '—' }}</p>
                            </div>
                            @if ($tareaSeleccionada->horas_estimadas)
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800 px-3 py-2 col-span-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Horas estimadas (subtareas)</p>
                                    <p class="font-medium text-slate-700 dark:text-slate-200">{{ rtrim(rtrim(number_format($tareaSeleccionada->horas_estimadas, 2), '0'), '.') }} h</p>
                                </div>
                            @endif
                        </div>

                        @if ($tareaSeleccionada->descripcion)
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1">Descripción</p>
                                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ $tareaSeleccionada->descripcion }}</p>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <button wire:click="iniciarEdicion"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.97] transition">
                                <x-icon name="report" class="w-3.5 h-3.5" /> Editar tarea
                            </button>
                            @if ($puedeRechazar)
                                <button wire:click="iniciarRechazo"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-3 py-1.5 text-xs font-medium text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-500/20 active:scale-[0.97] transition">
                                    <x-icon name="alert" class="w-3.5 h-3.5" /> Rechazar
                                </button>
                            @endif
                        </div>

                        {{-- Rechazo: solicita motivo obligatorio y deja trazabilidad --}}
                        @if ($rechazando)
                            <div class="rounded-xl border border-rose-200 dark:border-rose-500/30 bg-rose-50/60 dark:bg-rose-500/10 p-4 space-y-2">
                                <p class="text-sm font-semibold text-rose-700 dark:text-rose-400">Rechazar tarea completada</p>
                                <label for="motivoRechazo" class="sr-only">Motivo del rechazo</label>
                                <textarea id="motivoRechazo" wire:model="motivoRechazo" rows="2" required
                                          placeholder="Motivo del rechazo (obligatorio)..."
                                          class="w-full resize-none rounded-lg border-rose-300 dark:border-rose-500/40 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                @error('motivoRechazo') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                                <div class="flex gap-2">
                                    <button wire:click="confirmarRechazo" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 active:scale-[0.97] transition">Confirmar rechazo</button>
                                    <button wire:click="cancelarRechazo" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.97] transition">Cancelar</button>
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Edicion en linea --}}
                        <form wire:submit="guardarEdicion" class="space-y-3 rounded-2xl border border-blue-100 dark:border-blue-500/30 bg-blue-50/40 dark:bg-blue-500/10 p-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Título</label>
                                <input type="text" wire:model="edTitulo"
                                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('edTitulo') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Descripción</label>
                                <textarea wire:model="edDescripcion" rows="2"
                                          class="w-full resize-none rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Asignar a</label>
                                    <select wire:model.live="edAsignadoId" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($empleados as $e)
                                            <option value="{{ $e->id }}">{{ $e->name }} · {{ $e->carga['porcentaje'] }}% carga</option>
                                        @endforeach
                                    </select>
                                    @error('edAsignadoId') <span class="block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Estado</label>
                                    <select wire:model="edEstado" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach ($estadosLabel as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Tipo</label>
                                    <select wire:model="edTipo" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="software">Software</option>
                                        <option value="soporte">Soporte</option>
                                        <option value="infraestructura">Infraestructura</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Prioridad</label>
                                    <select wire:model="edPrioridad" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="baja">Baja</option>
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="critica">Critica</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Previsualizacion de carga resultante antes de guardar --}}
                            @if ($edCargaPrevia && $edCargaPrevia['disponibles'] !== null)
                                <div class="rounded-xl border {{ $edCargaPrevia['ok'] ? 'border-blue-100 dark:border-blue-500/30 bg-blue-50/40 dark:bg-blue-500/10' : 'border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10' }} p-3 text-xs">
                                    @if ($edCargaPrevia['ok'])
                                        <p class="text-blue-700 dark:text-blue-300">Carga resultante: {{ $edCargaPrevia['asignadas'] }} h de {{ $edCargaPrevia['disponibles'] }} h disponibles.</p>
                                    @else
                                        <p class="font-semibold text-rose-700 dark:text-rose-400 flex items-start gap-1.5"><x-icon name="alert" class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ $edCargaPrevia['mensaje'] }}</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Modificacion manual de la fecha limite: solo administrador --}}
                            @if ($esAdmin)
                                <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-500/10 p-3 space-y-2">
                                    <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                        <x-icon name="alert" class="w-3.5 h-3.5" />
                                        <p class="text-xs font-semibold">Modificar fecha límite (solo administrador)</p>
                                    </div>
                                    <div>
                                        <input type="datetime-local" wire:model.live="edFechaLimiteInput"
                                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        @error('edFechaLimiteInput') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                                    </div>
                                    @if ($edFechaLimiteCambiada)
                                        <div>
                                            <textarea wire:model="edObservacionFecha" rows="2"
                                                      placeholder="Observación obligatoria: motivo del cambio de fecha límite..."
                                                      class="w-full resize-none rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                            @error('edObservacionFecha') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center gap-2 pt-1">
                                <button type="submit"
                                        class="rounded-lg bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                                    Guardar cambios
                                </button>
                                <button type="button" wire:click="cancelarEdicion"
                                        class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.98] transition">
                                    Cancelar
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500">Al cambiar el estado, la tarjeta se mueve a la columna correspondiente y se recalcula el SLA/progreso.</p>
                        </form>
                    @endif

                    {{-- Subtareas: desglosan la tarea en horas, que se suman a "Horas estimadas" --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Subtareas</h3>
                            <span class="text-xs font-medium text-slate-400 dark:text-slate-500">
                                Total: {{ $tareaSeleccionada->horas_estimadas ? rtrim(rtrim(number_format($tareaSeleccionada->horas_estimadas, 2), '0'), '.') : 0 }} h
                            </span>
                        </div>

                        <div class="space-y-1.5 mb-3">
                            @forelse ($tareaSeleccionada->subtareas as $s)
                                <div wire:key="subtarea-{{ $s->id }}" class="flex items-center justify-between rounded-lg bg-slate-50 dark:bg-slate-800 px-3 py-2 text-sm">
                                    <span class="text-slate-700 dark:text-slate-200">{{ $s->titulo }}</span>
                                    <span class="flex items-center gap-2 shrink-0 ml-3">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400 tabular-nums">{{ rtrim(rtrim(number_format($s->horas, 2), '0'), '.') }} h</span>
                                        @if ($puedeEliminarSubtarea)
                                            <button wire:click="eliminarSubtarea({{ $s->id }})" wire:confirm="¿Eliminar esta subtarea?"
                                                    aria-label="Eliminar subtarea {{ $s->titulo }}" title="Eliminar subtarea"
                                                    class="rounded p-0.5 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                                                <x-icon name="close" class="w-3.5 h-3.5" />
                                            </button>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400 dark:text-slate-500">Sin subtareas todavía.</p>
                            @endforelse
                        </div>

                        @if ($puedeCrearSubtarea)
                            <form wire:submit="agregarSubtarea" class="flex items-start gap-2">
                                <div class="flex-1">
                                    <label for="nuevaSubtareaTitulo" class="sr-only">Título de la subtarea</label>
                                    <input id="nuevaSubtareaTitulo" type="text" wire:model="nuevaSubtareaTitulo" placeholder="Título de la subtarea"
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('nuevaSubtareaTitulo') <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-24">
                                    <label for="nuevaSubtareaHoras" class="sr-only">Horas estimadas</label>
                                    <input id="nuevaSubtareaHoras" type="number" step="0.5" min="0.5" wire:model="nuevaSubtareaHoras" placeholder="Horas"
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <button type="submit" aria-label="Agregar subtarea"
                                        class="shrink-0 inline-flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/15 px-3 py-2 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/25 active:scale-95 transition">
                                    <x-icon name="plus" class="w-4 h-4" />
                                </button>
                            </form>
                            @error('nuevaSubtareaHoras') <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    {{-- Foro / trazabilidad --}}
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Discusión y trazabilidad</h3>

                        @php
                            $meta = [
                                'comentario'          => ['Comentario', 'support', 'text-blue-500 dark:text-blue-400'],
                                'creacion'            => ['Creación', 'plus', 'text-emerald-500 dark:text-emerald-400'],
                                'actualizacion'       => ['Actualización', 'report', 'text-slate-400 dark:text-slate-500'],
                                'cambio_estado'       => ['Cambio de estado', 'trend', 'text-sky-500 dark:text-sky-400'],
                                'reasignacion'        => ['Reasignación', 'users', 'text-orange-500 dark:text-orange-400'],
                                'cambio_prioridad'    => ['Cambio de prioridad', 'alert', 'text-amber-500 dark:text-amber-400'],
                                'cambio_fecha_limite' => ['Cambio de fecha límite', 'calendar', 'text-rose-400'],
                                'subtarea'            => ['Subtarea', 'tasks', 'text-teal-500 dark:text-teal-400'],
                                'rechazo'             => ['Rechazo del evaluador', 'alert', 'text-rose-500 dark:text-rose-400'],
                                'bloqueo_capacidad'   => ['Bloqueo por capacidad', 'alert', 'text-amber-500 dark:text-amber-400'],
                            ];

                            // Referencias @{id} a subtareas de esta misma tarea, para citarlas en comentarios.
                            $subtareasPorId = $tareaSeleccionada->subtareas->keyBy('id');
                            $resolverMenciones = function (string $texto) use ($subtareasPorId) {
                                return preg_replace_callback('/@\{(\d+)\}/', function ($m) use ($subtareasPorId) {
                                    $s = $subtareasPorId->get((int) $m[1]);
                                    return $s
                                        ? '<span class="inline-flex items-center gap-0.5 rounded bg-blue-100 dark:bg-blue-500/20 px-1.5 py-0.5 text-blue-700 dark:text-blue-300 font-medium">↳ ' . e($s->titulo) . '</span>'
                                        : $m[0];
                                }, e($texto));
                            };
                        @endphp

                        <div class="space-y-3">
                            @forelse ($tareaSeleccionada->actividades as $act)
                                @php [$lbl, $ico, $col] = $meta[$act->accion] ?? [ucfirst($act->accion), 'dot', 'text-slate-400 dark:text-slate-500']; @endphp
                                @if ($act->accion === 'comentario')
                                    <div class="flex gap-2.5">
                                        <x-avatar :usuario="$act->user" :nombre="$act->user ? null : 'Sistema'" size="h-8 w-8" text="text-[11px]" tone="muted" />
                                        <div class="min-w-0 flex-1 rounded-xl rounded-tl-sm bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $act->user?->name ?? 'Sistema' }}</span>
                                                <span class="flex items-center gap-2">
                                                    <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ $act->created_at->diffForHumans() }}</span>
                                                    @if ($puedeEliminarComentario)
                                                        <button wire:click="eliminarComentario({{ $act->id }})" wire:confirm="¿Eliminar este comentario?"
                                                                aria-label="Eliminar comentario" title="Eliminar comentario"
                                                                class="rounded p-0.5 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                                                            <x-icon name="close" class="w-3 h-3" />
                                                        </button>
                                                    @endif
                                                </span>
                                            </div>
                                            <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{!! $resolverMenciones($act->detalle) !!}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 {{ $col }}">
                                            <x-icon :name="$ico" class="w-4 h-4" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-slate-600 dark:text-slate-300"><span class="font-medium text-slate-700 dark:text-slate-200">{{ $lbl }}</span> · {{ $act->detalle }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ $act->user?->name ?? 'Sistema' }} · {{ $act->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <p class="text-sm text-slate-400 dark:text-slate-500">Sin actividad registrada aún.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Nuevo comentario --}}
                @if ($puedeCrearComentario)
                    <div class="border-t border-slate-100 dark:border-slate-800 px-6 py-4">
                        <form wire:submit="comentar" class="flex items-end gap-2"
                              x-data="{
                                  subtareas: @js($tareaSeleccionada->subtareas->map(fn ($s) => ['id' => $s->id, 'titulo' => $s->titulo])),
                                  open: false, query: '', filtradas: [],
                                  buscar(e) {
                                      const val = e.target.value;
                                      const at = val.lastIndexOf('@');
                                      if (at === -1 || /\s/.test(val.slice(at + 1))) { this.open = false; return; }
                                      this.query = val.slice(at + 1).toLowerCase();
                                      this.filtradas = this.subtareas.filter(s => s.titulo.toLowerCase().includes(this.query)).slice(0, 6);
                                      this.open = this.filtradas.length > 0;
                                  },
                                  elegir(s) {
                                      const el = $refs.comentario;
                                      const val = el.value;
                                      const at = val.lastIndexOf('@');
                                      const nuevo = val.slice(0, at) + '@{' + s.id + '} ';
                                      $wire.set('nuevoComentario', nuevo);
                                      this.open = false;
                                      $nextTick(() => el.focus());
                                  },
                              }">
                            <div class="flex-1 relative">
                                <label for="nuevoComentario" class="sr-only">Nuevo comentario</label>
                                <textarea id="nuevoComentario" x-ref="comentario" wire:model="nuevoComentario" rows="2" placeholder="Escribe un comentario… usa @ para citar una subtarea"
                                          @input="buscar" @keydown.escape="open = false"
                                          class="w-full resize-none rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                <div x-show="open" style="display:none;" @click.outside="open = false"
                                     class="absolute bottom-full left-0 z-10 mb-1 w-64 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg py-1"
                                     role="listbox" aria-label="Subtareas para citar">
                                    <template x-for="s in filtradas" :key="s.id">
                                        <button type="button" @click="elegir(s)" role="option"
                                                class="block w-full text-left px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-500/15 focus-visible:outline-none focus-visible:bg-blue-50 dark:focus-visible:bg-blue-500/15"
                                                x-text="'↳ ' + s.titulo"></button>
                                    </template>
                                </div>
                                @error('nuevoComentario') <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit"
                                    class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                                Publicar
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
