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
                <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->subDepartamentoNombre() }}) · {{ $e->carga['porcentaje'] }}% carga</option>
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

    {{-- Subdepartamento --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subdepartamento *</label>
        <select wire:model.live="sub_department_id" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
            <option value="">Selecciona un subdepartamento</option>
            @foreach ($subDepartamentos as $sd)
                <option value="{{ $sd->id }}">{{ $sd->nombre }}</option>
            @endforeach
        </select>
        @error('sub_department_id') <span class="block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
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
