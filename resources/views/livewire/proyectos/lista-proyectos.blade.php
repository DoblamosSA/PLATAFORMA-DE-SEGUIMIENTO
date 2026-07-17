<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    <x-page-header title="Proyectos" subtitle="Software, soporte e infraestructura" icon="folder">
        <x-slot:actions>
            <a href="{{ route('proyectos.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 transition">
                <x-icon name="plus" class="w-4 h-4" /> Nuevo proyecto
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filtros --}}
    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar proyecto..."
                   class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select wire:model.live="tipo" class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos los tipos</option>
                <option value="software">Software</option>
                <option value="soporte">Soporte</option>
                <option value="infraestructura">Infraestructura</option>
            </select>
            <select wire:model.live="estado" class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos los estados</option>
                <option value="planeado">Planeado</option>
                <option value="en_progreso">En progreso</option>
                <option value="en_pausa">En pausa</option>
                <option value="completado">Completado</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>
    </x-card>

    {{-- Tarjetas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($proyectos as $p)
            @php $ico = ['software'=>'code','soporte'=>'support','infraestructura'=>'server'][$p->tipo] ?? 'folder'; @endphp
            <a href="{{ route('proyectos.ver', $p) }}" wire:navigate
               class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 -top-px h-1 bg-gradient-to-r from-indigo-500 to-violet-500 opacity-0 group-hover:opacity-100 transition"></div>
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <x-icon :name="$ico" class="w-5 h-5" />
                        </span>
                        <h2 class="font-semibold text-slate-800 truncate group-hover:text-indigo-700">{{ $p->nombre }}</h2>
                    </div>
                    <x-badge tipo="estado" :valor="$p->estado" />
                </div>

                <div class="mt-3 flex gap-2">
                    <x-badge tipo="tipo" :valor="$p->tipo" />
                    <x-badge tipo="prioridad" :valor="$p->prioridad" />
                </div>

                <div class="mt-4">
                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                        <span>Progreso</span>
                        <span class="font-medium tabular-nums">{{ $p->progreso }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-700" style="width: {{ $p->progreso }}%"></div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between text-xs">
                    <span class="text-slate-500">{{ $p->tareas_completadas_count }}/{{ $p->tareas_count }} tareas</span>
                    @if ($p->tareas_vencidas_count > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 font-medium text-rose-600">
                            <x-icon name="alert" class="w-3.5 h-3.5" /> {{ $p->tareas_vencidas_count }} vencidas
                        </span>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3 text-xs text-slate-400">
                    <x-icon name="users" class="w-4 h-4" />
                    Responsable: {{ $p->responsable?->name ?? 'Sin asignar' }}
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white/60 py-12 text-center text-slate-400">
                No hay proyectos con estos filtros.
            </div>
        @endforelse
    </div>

    <div>{{ $proyectos->links() }}</div>
</div>
