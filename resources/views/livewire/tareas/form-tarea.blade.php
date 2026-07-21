<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto space-y-5">

        <x-page-header :title="$task ? 'Editar tarea' : 'Nueva tarea'" subtitle="Define la actividad y su asignacion" icon="tasks">
            <x-slot:actions>
                <a href="{{ route('tareas') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                    <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                </a>
            </x-slot:actions>
        </x-page-header>

        <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-5">

            {{-- Titulo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Titulo *</label>
                <input type="text" wire:model="titulo"
                       class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('titulo') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
            </div>

            {{-- Descripcion --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Descripcion</label>
                <textarea wire:model="descripcion" rows="3"
                          class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Proyecto --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Proyecto</label>
                    <select wire:model.live="project_id" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        <option value="">— Actividad suelta (sin proyecto) —</option>
                        @foreach ($proyectos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Asignado --}}
                <div>
                    <label for="asignado_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Asignar a</label>
                    <select id="asignado_id" wire:model.live="asignado_id" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        <option value="">— Sin asignar —</option>
                        @foreach ($empleados as $e)
                            <option value="{{ $e->id }}">{{ $e->name }} ({{ ucfirst($e->area) }}) · {{ $e->carga['porcentaje'] }}% carga</option>
                        @endforeach
                    </select>
                    @if ($project_id && $empleados->isEmpty())
                        <span class="text-xs text-amber-600 dark:text-amber-400">Este proyecto no tiene equipo aun. Agrega desarrolladores en el proyecto.</span>
                    @elseif ($project_id)
                        <span class="text-xs text-gray-400 dark:text-slate-500">Solo se muestran los integrantes del equipo del proyecto.</span>
                    @endif
                    @error('asignado_id') <span class="block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>

                {{-- Fecha de inicio planificada --}}
                <div>
                    <label for="fechaInicioInput" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Fecha de inicio planificada</label>
                    <input id="fechaInicioInput" type="date" wire:model.live="fechaInicioInput"
                           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="text-xs text-gray-400 dark:text-slate-500">Con inicio y vencimiento, las horas se reparten entre los días laborables del colaborador.</span>
                    @error('fechaInicioInput') <span class="block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Tipo *</label>
                    <select wire:model.live="tipo" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        <option value="software">Software</option>
                        <option value="soporte">Soporte</option>
                        <option value="infraestructura">Infraestructura</option>
                    </select>
                </div>

                {{-- Prioridad --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Prioridad *</label>
                    <select wire:model.live="prioridad" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Critica</option>
                    </select>
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Estado *</label>
                    <select wire:model="estado" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_progreso">En progreso</option>
                        <option value="en_revision">En revision</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>

                {{-- SLA preview --}}
                <div class="flex items-end">
                    <div class="w-full rounded-lg bg-blue-50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/30 px-4 py-2.5">
                        <p class="text-xs text-blue-500 dark:text-blue-400">SLA objetivo</p>
                        <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">{{ $slaHoras }} horas para resolver</p>
                    </div>
                </div>

                {{-- Tag (obligatorio y bloqueado para el evaluador) --}}
                @if ($tag || $tagBloqueado)
                    <div>
                        <label for="tag" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Etiqueta</label>
                        <input id="tag" type="text" wire:model="tag" @if ($tagBloqueado) disabled @endif
                               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-70 disabled:cursor-not-allowed">
                        @if ($tagBloqueado)
                            <span class="text-xs text-amber-600 dark:text-amber-400">Las tareas creadas por un evaluador siempre llevan la etiqueta "certificación".</span>
                        @endif
                        @error('tag') <span class="block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            {{-- Previsualizacion de carga resultante antes de guardar --}}
            @if ($cargaPrevia && $this->cargaPrevia['disponibles'] !== null)
                <div class="rounded-xl border {{ $cargaPrevia['ok'] ? 'border-blue-100 dark:border-blue-500/30 bg-blue-50/40 dark:bg-blue-500/10' : 'border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10' }} p-4 text-sm">
                    @if ($cargaPrevia['ok'])
                        <p class="text-blue-700 dark:text-blue-300">
                            Carga resultante: {{ $cargaPrevia['asignadas'] }} h asignadas de {{ $cargaPrevia['disponibles'] }} h disponibles en el período.
                        </p>
                    @else
                        <p class="font-semibold text-rose-700 dark:text-rose-400 flex items-start gap-2">
                            <x-icon name="alert" class="w-4 h-4 shrink-0 mt-0.5" /> {{ $cargaPrevia['mensaje'] }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($task && $task->fecha_limite)
                <div class="text-sm text-gray-500 dark:text-slate-400">
                    Vencimiento actual:
                    <span class="{{ $task->estaVencida() ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-gray-700 dark:text-slate-300 font-medium' }}">
                        {{ $task->fecha_limite->format('d/m/Y H:i') }}
                    </span>
                    @if ($task->cumplida_a_tiempo !== null)
                        · <x-badge tipo="estado" :valor="$task->cumplida_a_tiempo ? 'completada' : 'cancelada'" />
                        {{ $task->cumplida_a_tiempo ? 'Cumplida a tiempo' : 'Cerrada fuera de SLA' }}
                    @endif
                </div>
            @endif

            {{-- Modificacion manual de la fecha limite: solo administrador --}}
            @if ($task && $esAdmin)
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-500/10 p-4 space-y-3">
                    <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                        <x-icon name="alert" class="w-4 h-4" />
                        <p class="text-sm font-semibold">Modificar fecha límite (solo administrador)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Fecha y hora límite</label>
                        <input type="datetime-local" wire:model.live="fechaLimiteInput"
                               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('fechaLimiteInput') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    @if ($fechaLimiteCambiada)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Observación (obligatoria) *</label>
                            <textarea wire:model="observacionFecha" rows="2"
                                      placeholder="Explica el motivo del cambio de fecha límite..."
                                      class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            @error('observacionFecha') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    {{ $task ? 'Guardar cambios' : 'Crear tarea' }}
                </button>
                <a href="{{ route('tareas') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>

                @if ($puedeEliminar)
                    <button type="button" wire:click="eliminar"
                            wire:confirm="¿Eliminar esta tarea? Esta acción no se puede deshacer."
                            class="ml-auto text-sm font-medium text-rose-600 dark:text-rose-400 hover:underline">
                        Eliminar tarea
                    </button>
                @elseif ($task && $task->subtareas->isNotEmpty())
                    <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">No se puede eliminar: tiene subtareas.</span>
                @endif
            </div>
        </form>

        {{-- Bitacora --}}
        @if ($task && $bitacora->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Historial</h2>
                <ul class="space-y-2">
                    @foreach ($bitacora as $act)
                        <li class="flex items-start gap-3 text-sm">
                            <span class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-400 shrink-0"></span>
                            <div>
                                <span class="text-gray-700 dark:text-slate-300">{{ $act->detalle }}</span>
                                <span class="block text-xs text-gray-400 dark:text-slate-500">
                                    {{ $act->user?->name ?? 'Sistema' }} · {{ $act->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</div>
