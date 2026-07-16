<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $task ? 'Editar tarea' : 'Nueva tarea' }}
            </h1>
            <a href="{{ route('tareas') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Volver</a>
        </div>

        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

            {{-- Titulo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titulo *</label>
                <input type="text" wire:model="titulo"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('titulo') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            {{-- Descripcion --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                <textarea wire:model="descripcion" rows="3"
                          class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Proyecto --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto</label>
                    <select wire:model.live="project_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— Actividad suelta (sin proyecto) —</option>
                        @foreach ($proyectos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Asignado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a</label>
                    <select wire:model="asignado_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— Sin asignar —</option>
                        @foreach ($empleados as $e)
                            <option value="{{ $e->id }}">{{ $e->name }} ({{ ucfirst($e->area) }})</option>
                        @endforeach
                    </select>
                    @if ($project_id && $empleados->isEmpty())
                        <span class="text-xs text-amber-600">Este proyecto no tiene equipo aun. Agrega desarrolladores en el proyecto.</span>
                    @elseif ($project_id)
                        <span class="text-xs text-gray-400">Solo se muestran los integrantes del equipo del proyecto.</span>
                    @endif
                    @error('asignado_id') <span class="block text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select wire:model.live="tipo" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="software">Software</option>
                        <option value="soporte">Soporte</option>
                        <option value="infraestructura">Infraestructura</option>
                    </select>
                </div>

                {{-- Prioridad --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                    <select wire:model.live="prioridad" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Critica</option>
                    </select>
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select wire:model="estado" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_progreso">En progreso</option>
                        <option value="en_revision">En revision</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>

                {{-- SLA preview --}}
                <div class="flex items-end">
                    <div class="w-full rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-2.5">
                        <p class="text-xs text-indigo-500">SLA objetivo</p>
                        <p class="text-sm font-semibold text-indigo-700">{{ $this->slaHoras }} horas para resolver</p>
                    </div>
                </div>
            </div>

            @if ($task && $task->fecha_limite)
                <div class="text-sm text-gray-500">
                    Vencimiento actual:
                    <span class="{{ $task->estaVencida() ? 'text-rose-600 font-semibold' : 'text-gray-700 font-medium' }}">
                        {{ $task->fecha_limite->format('d/m/Y H:i') }}
                    </span>
                    @if ($task->cumplida_a_tiempo !== null)
                        · <x-badge tipo="estado" :valor="$task->cumplida_a_tiempo ? 'completada' : 'cancelada'" />
                        {{ $task->cumplida_a_tiempo ? 'Cumplida a tiempo' : 'Cerrada fuera de SLA' }}
                    @endif
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ $task ? 'Guardar cambios' : 'Crear tarea' }}
                </button>
                <a href="{{ route('tareas') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
            </div>
        </form>

        {{-- Bitacora --}}
        @if ($task && $bitacora->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Historial</h2>
                <ul class="space-y-2">
                    @foreach ($bitacora as $act)
                        <li class="flex items-start gap-3 text-sm">
                            <span class="mt-1 h-1.5 w-1.5 rounded-full bg-indigo-400 shrink-0"></span>
                            <div>
                                <span class="text-gray-700">{{ $act->detalle }}</span>
                                <span class="block text-xs text-gray-400">
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
