<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nombre *</label>
    <input type="text" wire:model="nombre"
           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
    @error('nombre') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Descripcion</label>
    <textarea wire:model="descripcion" rows="3"
              class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subdepartamento *</label>
        <select wire:model="sub_department_id" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
            <option value="">Selecciona un subdepartamento</option>
            @foreach ($subDepartamentos as $sd)
                <option value="{{ $sd->id }}">{{ $sd->nombre }}</option>
            @endforeach
        </select>
        @error('sub_department_id') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Responsable</label>
        <select wire:model="responsable_id" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
            <option value="">— Sin asignar —</option>
            @foreach ($lideres as $l)
                <option value="{{ $l->id }}">{{ $l->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Estado *</label>
        <select wire:model="estado" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
            <option value="planeado">Planeado</option>
            <option value="en_progreso">En progreso</option>
            <option value="en_pausa">En pausa</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Prioridad *</label>
        <select wire:model="prioridad" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
            <option value="critica">Critica</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Fecha inicio</label>
        <input type="date" wire:model="fecha_inicio"
               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Fecha fin estimada</label>
        <input type="date" wire:model="fecha_fin_estimada"
               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        @error('fecha_fin_estimada') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
    </div>
</div>

{{-- Equipo del proyecto (desarrolladores) --}}
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
        Equipo del proyecto
        <span class="text-xs font-normal text-gray-400 dark:text-slate-500">— solo estas personas podran recibir tareas del proyecto</span>
    </label>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        @foreach ($empleados as $e)
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-slate-700 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors
                          {{ in_array((string) $e->id, $equipo) ? 'bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/40' : '' }}">
                <input type="checkbox" wire:model.live="equipo" value="{{ $e->id }}"
                       class="rounded border-gray-300 dark:border-slate-600 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                <span class="min-w-0">
                    <span class="block truncate text-gray-700 dark:text-slate-200">{{ $e->name }}</span>
                    <span class="block text-xs text-gray-400 dark:text-slate-500 capitalize">{{ $e->area }}</span>
                </span>
            </label>
        @endforeach
    </div>
    @error('equipo') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
</div>

@if ($project)
    <div class="rounded-lg bg-gray-50 dark:bg-slate-800/60 border border-gray-100 dark:border-slate-700 px-4 py-3 text-sm text-gray-600 dark:text-slate-400">
        Progreso actual: <span class="font-semibold text-gray-800 dark:text-slate-200">{{ $project->progreso }}%</span>
        · {{ $project->tareas()->count() }} tareas
        @if (auth()->user()->esAdmin())
            · <a href="{{ route('tareas') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">ver tareas</a>
        @endif
    </div>
@endif
