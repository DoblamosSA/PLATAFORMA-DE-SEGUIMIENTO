<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    {{-- Encabezado --}}
    <x-page-header title="Cumplimiento y SLA" subtitle="Panorama de actividades y proyectos de tecnologia" icon="dashboard">
        <x-slot:actions>
            <select wire:model.live="rango"
                    class="rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach ($periodos as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </x-slot:actions>
    </x-page-header>

    {{-- Fila principal: gauge + KPIs --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Gauge de cumplimiento --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm dark:shadow-black/20">
            <div class="absolute inset-x-0 -top-px h-1 bg-gradient-to-r from-blue-500 via-sky-500 to-cyan-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Cumplimiento SLA</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $periodos[$rango] }}</p>
                </div>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400">
                    <x-icon name="sparkles" class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2 flex items-center justify-center">
                <x-gauge :value="$resumen['cumplimiento']" label="a tiempo" />
            </div>
            <p class="text-center text-xs text-slate-400 dark:text-slate-500">{{ $resumen['a_tiempo'] }} de {{ $resumen['completadas'] }} completadas dentro del plazo</p>
        </div>

        {{-- KPIs --}}
        <div class="lg:col-span-2 grid grid-cols-2 gap-5">
            <x-stat label="Vencidas abiertas" :value="$resumen['abiertas_vencidas']" icon="alert" tone="rose"
                    hint="{{ $resumen['abiertas'] }} tareas abiertas en total" />
            <x-stat label="Tiempo promedio" :value="$resumen['tiempo_promedio'] !== null ? $resumen['tiempo_promedio'].'h' : '—'" icon="clock" tone="sky"
                    hint="Resolucion en el periodo" />
            <x-stat label="Completadas" :value="$resumen['completadas']" icon="check" tone="emerald"
                    hint="{{ $resumen['vencidas_cerradas'] }} cerradas fuera de SLA" />
            <x-stat label="Total asignadas" :value="$resumen['total']" icon="tasks" tone="indigo"
                    hint="En el periodo seleccionado" />
        </div>
    </div>

    {{-- Por tipo + por persona --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <x-card title="Cumplimiento por tipo" class="lg:col-span-2">
            <div class="space-y-5">
                @foreach ($porTipo as $fila)
                    @php $tint = ['software' => 'indigo', 'soporte' => 'teal', 'infraestructura' => 'cyan'][$fila['tipo']] ?? 'slate'; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="flex items-center gap-2 capitalize font-medium text-slate-600 dark:text-slate-300">
                                <x-icon :name="['software'=>'code','soporte'=>'support','infraestructura'=>'server'][$fila['tipo']] ?? 'dot'" class="w-4 h-4 text-slate-400 dark:text-slate-500" />
                                {{ $fila['tipo'] }}
                            </span>
                            <span class="text-slate-500 dark:text-slate-400 tabular-nums">{{ $fila['cumplimiento'] }}%
                                <span class="text-slate-300 dark:text-slate-600">·</span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }}</span>
                            </span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700
                                @if ($fila['cumplimiento'] >= 90) bg-gradient-to-r from-emerald-400 to-emerald-500
                                @elseif ($fila['cumplimiento'] >= 70) bg-gradient-to-r from-amber-400 to-amber-500
                                @else bg-gradient-to-r from-rose-400 to-rose-500 @endif"
                                style="width: {{ max($fila['cumplimiento'], 2) }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ $fila['abiertas'] }} abiertas</p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Cumplimiento por persona" class="lg:col-span-3">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                            <th class="py-2 pr-2 font-medium">Empleado</th>
                            <th class="py-2 px-2 font-medium text-center">A tiempo</th>
                            <th class="py-2 px-2 font-medium text-center">Abiertas</th>
                            <th class="py-2 px-2 font-medium text-center">Vencidas</th>
                            <th class="py-2 pl-2 font-medium text-right">Cumpl.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse ($porPersona as $fila)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 pr-2">
                                    <div class="flex items-center gap-2.5">
                                        <x-avatar :usuario="$fila['usuario']" size="h-8 w-8" tone="muted" />
                                        <div>
                                            <p class="font-medium text-slate-700 dark:text-slate-200 leading-tight">{{ $fila['usuario']->name }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 capitalize">{{ $fila['usuario']->area }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-2 text-center text-slate-600 dark:text-slate-300 tabular-nums">{{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }}</td>
                                <td class="py-2.5 px-2 text-center text-slate-600 dark:text-slate-300 tabular-nums">{{ $fila['abiertas'] }}</td>
                                <td class="py-2.5 px-2 text-center tabular-nums {{ $fila['vencidas'] > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-slate-300 dark:text-slate-600' }}">{{ $fila['vencidas'] }}</td>
                                <td class="py-2.5 pl-2 text-right">
                                    @if ($fila['cumplimiento'] === null)
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                            @if ($fila['cumplimiento'] >= 90) bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400
                                            @elseif ($fila['cumplimiento'] >= 70) bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400
                                            @else bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400 @endif">{{ $fila['cumplimiento'] }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-400 dark:text-slate-500">Sin datos en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Vencidas + proximas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <x-card>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 dark:bg-rose-500/15 text-rose-600 dark:text-rose-400"><x-icon name="alert" class="w-5 h-5" /></span>
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tareas vencidas</h2>
            </div>
            @forelse ($vencidas as $t)
                <a @if (auth()->user()->esAdmin()) href="{{ route('tareas.editar', $t) }}" wire:navigate @endif
                   class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 -mx-1 transition hover:bg-rose-50/60 dark:hover:bg-rose-500/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate group-hover:text-rose-700 dark:group-hover:text-rose-400">{{ $t->titulo }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $t->asignado?->name ?? 'Sin asignar' }} · {{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-rose-100 dark:bg-rose-500/15 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:text-rose-400">{{ $t->fecha_limite->diffForHumans() }}</span>
                </a>
            @empty
                <div class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">Ninguna tarea vencida. 🎉</div>
            @endforelse
        </x-card>

        <x-card>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400"><x-icon name="clock" class="w-5 h-5" /></span>
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Proximas a vencer</h2>
            </div>
            @forelse ($proximasVencer as $t)
                <a @if (auth()->user()->esAdmin()) href="{{ route('tareas.editar', $t) }}" wire:navigate @endif
                   class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 -mx-1 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate group-hover:text-blue-700 dark:group-hover:text-blue-400">{{ $t->titulo }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $t->asignado?->name ?? 'Sin asignar' }} · {{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">{{ $t->fecha_limite->diffForHumans() }}</span>
                </a>
            @empty
                <div class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">Nada pendiente por vencer.</div>
            @endforelse
        </x-card>
    </div>
</div>
