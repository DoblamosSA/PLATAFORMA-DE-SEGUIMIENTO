<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-3xl mx-auto space-y-5">

        <x-page-header :title="$project ? 'Editar proyecto' : 'Nuevo proyecto'" subtitle="Datos del proyecto y su equipo" icon="folder">
            <x-slot:actions>
                <a href="{{ route('proyectos') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                    <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                </a>
            </x-slot:actions>
        </x-page-header>

        <form wire:submit="save" class="rounded-2xl bg-white shadow-sm border border-slate-200/70 p-6 space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" wire:model="nombre"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('nombre') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                <textarea wire:model="descripcion" rows="3"
                          class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select wire:model="tipo" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="software">Software</option>
                        <option value="soporte">Soporte</option>
                        <option value="infraestructura">Infraestructura</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
                    <select wire:model="responsable_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— Sin asignar —</option>
                        @foreach ($lideres as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select wire:model="estado" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="planeado">Planeado</option>
                        <option value="en_progreso">En progreso</option>
                        <option value="en_pausa">En pausa</option>
                        <option value="completado">Completado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                    <select wire:model="prioridad" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Critica</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                    <input type="date" wire:model="fecha_inicio"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin estimada</label>
                    <input type="date" wire:model="fecha_fin_estimada"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('fecha_fin_estimada') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Equipo del proyecto (desarrolladores) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Equipo del proyecto
                    <span class="text-xs font-normal text-gray-400">— solo estas personas podran recibir tareas del proyecto</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach ($empleados as $e)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50
                                      {{ in_array((string) $e->id, $equipo) ? 'bg-indigo-50 border-indigo-200' : '' }}">
                            <input type="checkbox" wire:model.live="equipo" value="{{ $e->id }}"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="min-w-0">
                                <span class="block truncate text-gray-700">{{ $e->name }}</span>
                                <span class="block text-xs text-gray-400 capitalize">{{ $e->area }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('equipo') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            @if ($project)
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-sm text-gray-600">
                    Progreso actual: <span class="font-semibold text-gray-800">{{ $project->progreso }}%</span>
                    · {{ $project->tareas()->count() }} tareas
                    · <a href="{{ route('tareas', ['tipo' => '']) }}" wire:navigate class="text-indigo-600 hover:underline">ver tareas</a>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 transition">
                    {{ $project ? 'Guardar cambios' : 'Crear proyecto' }}
                </button>
                <a href="{{ route('proyectos') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">Cancelar</a>
            </div>
        </form>
    </div>
</div>
