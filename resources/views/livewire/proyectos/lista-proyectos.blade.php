<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Proyectos</h1>
            <a href="{{ route('proyectos.crear') }}" wire:navigate
               class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Nuevo proyecto
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar proyecto..."
                       class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select wire:model.live="tipo" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los tipos</option>
                    <option value="software">Software</option>
                    <option value="soporte">Soporte</option>
                    <option value="infraestructura">Infraestructura</option>
                </select>
                <select wire:model.live="estado" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="planeado">Planeado</option>
                    <option value="en_progreso">En progreso</option>
                    <option value="en_pausa">En pausa</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        {{-- Tarjetas de proyecto --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($proyectos as $p)
                <a href="{{ route('proyectos.editar', $p) }}" wire:navigate
                   class="block bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="font-semibold text-gray-800">{{ $p->nombre }}</h2>
                        <x-badge tipo="estado" :valor="$p->estado" />
                    </div>
                    <div class="mt-2 flex gap-2">
                        <x-badge tipo="tipo" :valor="$p->tipo" />
                        <x-badge tipo="prioridad" :valor="$p->prioridad" />
                    </div>

                    {{-- Progreso --}}
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Progreso</span>
                            <span>{{ $p->progreso }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $p->progreso }}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ $p->tareas_completadas_count }}/{{ $p->tareas_count }} tareas</span>
                        @if ($p->tareas_vencidas_count > 0)
                            <span class="text-rose-600 font-semibold">{{ $p->tareas_vencidas_count }} vencidas</span>
                        @endif
                    </div>

                    <p class="mt-3 text-xs text-gray-400">
                        Responsable: {{ $p->responsable?->name ?? 'Sin asignar' }}
                    </p>
                </a>
            @empty
                <div class="col-span-full py-8 text-center text-gray-400">No hay proyectos con estos filtros.</div>
            @endforelse
        </div>

        <div>{{ $proyectos->links() }}</div>
    </div>
</div>
