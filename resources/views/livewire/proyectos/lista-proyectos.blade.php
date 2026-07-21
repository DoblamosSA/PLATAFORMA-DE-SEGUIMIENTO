<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="Proyectos" subtitle="Software, soporte e infraestructura" icon="folder">
        <x-slot:actions>
            @if (auth()->user()->puedeCrearProyecto())
                <a href="{{ route('proyectos.crear') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    <x-icon name="plus" class="w-4 h-4" /> Nuevo proyecto
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Resumen por area --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total --}}
        <button type="button" wire:click="$set('tipo', '')"
                class="group relative overflow-hidden rounded-2xl border p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md
                       {{ $tipo === '' ? 'border-indigo-300 bg-indigo-50/60 ring-2 ring-indigo-200 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:ring-indigo-500/30' : 'border-slate-200/70 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Todas las areas</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-slate-100">{{ $totalProyectos }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">proyectos en total</p>
                </div>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-lg">
                    <x-icon name="folder" class="w-5 h-5" />
                </span>
            </div>
        </button>

        @php
            $areaMeta = [
                'software'        => ['Software', 'code', 'from-indigo-500 to-violet-600', 'text-indigo-600 dark:text-indigo-400', 'border-indigo-300 bg-indigo-50/60 ring-indigo-200 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:ring-indigo-500/30'],
                'soporte'         => ['Soporte', 'support', 'from-teal-500 to-emerald-600', 'text-teal-600 dark:text-teal-400', 'border-teal-300 bg-teal-50/60 ring-teal-200 dark:border-teal-500/40 dark:bg-teal-500/10 dark:ring-teal-500/30'],
                'infraestructura' => ['Infraestructura', 'server', 'from-cyan-500 to-sky-600', 'text-cyan-600 dark:text-cyan-400', 'border-cyan-300 bg-cyan-50/60 ring-cyan-200 dark:border-cyan-500/40 dark:bg-cyan-500/10 dark:ring-cyan-500/30'],
            ];
        @endphp
        @foreach ($resumenAreas as $a)
            @php [$nombre, $ico, $grad, $txt, $activeCls] = $areaMeta[$a['tipo']]; $sel = $tipo === $a['tipo']; @endphp
            <button type="button" wire:click="toggleArea('{{ $a['tipo'] }}')"
                    class="group relative overflow-hidden rounded-2xl border p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md
                           {{ $sel ? $activeCls.' ring-2' : 'border-slate-200/70 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20' }}">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $grad }} opacity-[0.07] transition group-hover:opacity-[0.14]"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $nombre }}</p>
                        <p class="mt-2 text-3xl font-bold {{ $txt }}">{{ $a['total'] }}</p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                            <span class="text-slate-500 dark:text-slate-400">{{ $a['activos'] }} activos</span> · {{ $a['completados'] }} completados
                        </p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-lg">
                        <x-icon :name="$ico" class="w-5 h-5" />
                    </span>
                </div>
                @if ($sel)
                    <span class="mt-3 inline-flex items-center gap-1 text-[11px] font-medium {{ $txt }}">● Filtrando por esta area</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Filtros --}}
    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar proyecto..."
                   class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500">
            <select wire:model.live="tipo" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los tipos</option>
                <option value="software">Software</option>
                <option value="soporte">Soporte</option>
                <option value="infraestructura">Infraestructura</option>
            </select>
            <select wire:model.live="estado" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
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
            @php
                $ico = ['software'=>'code','soporte'=>'support','infraestructura'=>'server'][$p->tipo] ?? 'folder';
                $cumplimiento = $p->tareas_completadas_count > 0
                    ? round($p->tareas_a_tiempo_count / $p->tareas_completadas_count * 100, 1)
                    : null;
                $semaforo = $p->semaforo();
                $tonosTarjeta = [
                    'saludable' => 'bg-emerald-50/60 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 hover:border-emerald-300 dark:hover:border-emerald-500/50',
                    'vencido'   => 'bg-rose-50/60 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/30 hover:border-rose-300 dark:hover:border-rose-500/50',
                    'en_riesgo' => 'bg-amber-50/60 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 hover:border-amber-300 dark:hover:border-amber-500/50',
                    'planeado'  => 'bg-sky-50/60 dark:bg-sky-500/10 border-sky-200 dark:border-sky-500/30 hover:border-sky-300 dark:hover:border-sky-500/50',
                ];
                $claseTarjeta = $tonosTarjeta[$semaforo] ?? 'bg-white dark:bg-slate-900 border-slate-200/70 dark:border-slate-800';
            @endphp
            <a href="{{ route('proyectos.ver', $p) }}" wire:navigate
               class="group relative flex flex-col overflow-hidden rounded-2xl border {{ $claseTarjeta }} p-5 shadow-sm dark:shadow-black/20 transition hover:shadow-lg hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950">
                <div class="absolute inset-x-0 -top-px h-1 bg-gradient-to-r from-blue-500 to-sky-500 opacity-0 group-hover:opacity-100 transition"></div>
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400">
                            <x-icon :name="$ico" class="w-5 h-5" />
                        </span>
                        <h2 class="font-semibold text-slate-800 dark:text-slate-100 truncate group-hover:text-blue-700 dark:group-hover:text-blue-400">{{ $p->nombre }}</h2>
                    </div>
                    <x-badge tipo="estado" :valor="$p->estado" />
                </div>

                <div class="mt-3 flex gap-2">
                    <x-badge tipo="tipo" :valor="$p->tipo" />
                    <x-badge tipo="prioridad" :valor="$p->prioridad" />
                </div>

                {{-- KPIs grandes: son el visualizador principal de la card --}}
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Progreso</p>
                        <p class="mt-0.5 text-3xl font-bold leading-none text-blue-600 dark:text-blue-400 tabular-nums">{{ $p->progreso }}%</p>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-sky-500 transition-all duration-700" style="width: {{ $p->progreso }}%"></div>
                        </div>
                    </div>
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Cumplimiento</p>
                        <p class="mt-0.5 text-3xl font-bold leading-none tabular-nums
                            @if ($cumplimiento === null) text-slate-300 dark:text-slate-600
                            @elseif ($cumplimiento >= 90) text-emerald-600 dark:text-emerald-400
                            @elseif ($cumplimiento >= 70) text-amber-600 dark:text-amber-400
                            @else text-rose-600 dark:text-rose-400 @endif">{{ $cumplimiento !== null ? rtrim(rtrim(number_format($cumplimiento, 1), '0'), '.').'%' : '—' }}</p>
                        <p class="mt-2 inline-flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500">
                            <x-icon name="calendar" class="w-3.5 h-3.5" /> {{ $p->fecha_fin_estimada?->format('d/m/Y') ?? 'Sin fecha' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">{{ $p->tareas_completadas_count }}/{{ $p->tareas_count }} tareas</span>
                    @if ($p->tareas_vencidas_count > 0)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-rose-100 dark:bg-rose-500/15 px-2.5 py-1 font-semibold text-rose-700 dark:text-rose-400">
                            <x-icon name="alert" class="w-3.5 h-3.5" /> {{ $p->tareas_vencidas_count }} {{ $p->tareas_vencidas_count === 1 ? 'vencida' : 'vencidas' }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 font-medium text-slate-400 dark:text-slate-500">
                            <x-icon name="check" class="w-3.5 h-3.5" /> Sin vencimientos
                        </span>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-slate-100 dark:border-slate-800 pt-3 text-xs text-slate-400 dark:text-slate-500">
                    <x-icon name="users" class="w-4 h-4" />
                    Responsable: {{ $p->responsable?->name ?? 'Sin asignar' }}
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white/60 dark:bg-slate-900/60 py-12 text-center text-slate-400 dark:text-slate-500">
                No hay proyectos con estos filtros.
            </div>
        @endforelse
    </div>

    <div>{{ $proyectos->links() }}</div>
</div>
