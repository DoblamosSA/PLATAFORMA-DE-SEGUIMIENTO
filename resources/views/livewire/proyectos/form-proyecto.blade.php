<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $project ? 'Editar proyecto' : 'Nuevo proyecto' }}
            </h1>
            <a href="{{ route('proyectos') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Volver</a>
        </div>

        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

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

            @if ($project)
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-sm text-gray-600">
                    Progreso actual: <span class="font-semibold text-gray-800">{{ $project->progreso }}%</span>
                    · {{ $project->tareas()->count() }} tareas
                    · <a href="{{ route('tareas', ['tipo' => '']) }}" wire:navigate class="text-indigo-600 hover:underline">ver tareas</a>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ $project ? 'Guardar cambios' : 'Crear proyecto' }}
                </button>
                <a href="{{ route('proyectos') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
            </div>
        </form>
    </div>
</div>
