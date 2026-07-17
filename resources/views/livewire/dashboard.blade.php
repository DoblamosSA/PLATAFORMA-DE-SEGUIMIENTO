<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Encabezado --}}
    <x-page-header title="Cumplimiento y SLA" subtitle="Panorama de actividades y proyectos de tecnologia" icon="dashboard">
        <x-slot:actions>
            <select wire:model.live="rango"
                    class="rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($this->periodos() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </x-slot:actions>
    </x-page-header>

    {{-- Fila principal: gauge + KPIs --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Gauge de cumplimiento --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <div class="absolute inset-x-0 -top-px h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Cumplimiento SLA</p>
                    <p class="text-xs text-slate-400">{{ $this->periodos()[$rango] }}</p>
                </div>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <x-icon name="sparkles" class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2 flex items-center justify-center">
                <x-gauge :value="$resumen['cumplimiento']" label="a tiempo" />
            </div>
            <p class="text-center text-xs text-slate-400">{{ $resumen['a_tiempo'] }} de {{ $resumen['completadas'] }} completadas dentro del plazo</p>
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
                            <span class="flex items-center gap-2 capitalize font-medium text-slate-600">
                                <x-icon :name="['software'=>'code','soporte'=>'support','infraestructura'=>'server'][$fila['tipo']] ?? 'dot'" class="w-4 h-4 text-slate-400" />
                                {{ $fila['tipo'] }}
                            </span>
                            <span class="text-slate-500 tabular-nums">{{ $fila['cumplimiento'] }}%
                                <span class="text-slate-300">·</span>
                                <span class="text-xs text-slate-400">{{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }}</span>
                            </span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700
                                @if ($fila['cumplimiento'] >= 90) bg-gradient-to-r from-emerald-400 to-emerald-500
                                @elseif ($fila['cumplimiento'] >= 70) bg-gradient-to-r from-amber-400 to-amber-500
                                @else bg-gradient-to-r from-rose-400 to-rose-500 @endif"
                                style="width: {{ max($fila['cumplimiento'], 2) }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">{{ $fila['abiertas'] }} abiertas</p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Cumplimiento por persona" class="lg:col-span-3">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400 border-b border-slate-100">
                            <th class="py-2 pr-2 font-medium">Empleado</th>
                            <th class="py-2 px-2 font-medium text-center">A tiempo</th>
                            <th class="py-2 px-2 font-medium text-center">Abiertas</th>
                            <th class="py-2 px-2 font-medium text-center">Vencidas</th>
                            <th class="py-2 pl-2 font-medium text-right">Cumpl.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($porPersona as $fila)
                            @php $ini = collect(explode(' ', $fila['usuario']->name))->take(2)->map(fn($p)=>mb_substr($p,0,1))->implode(''); @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-2.5 pr-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-slate-200 to-slate-300 text-xs font-semibold text-slate-600 uppercase">{{ $ini }}</span>
                                        <div>
                                            <p class="font-medium text-slate-700 leading-tight">{{ $fila['usuario']->name }}</p>
                                            <p class="text-[11px] text-slate-400 capitalize">{{ $fila['usuario']->area }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-2 text-center text-slate-600 tabular-nums">{{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }}</td>
                                <td class="py-2.5 px-2 text-center text-slate-600 tabular-nums">{{ $fila['abiertas'] }}</td>
                                <td class="py-2.5 px-2 text-center tabular-nums {{ $fila['vencidas'] > 0 ? 'text-rose-600 font-semibold' : 'text-slate-300' }}">{{ $fila['vencidas'] }}</td>
                                <td class="py-2.5 pl-2 text-right">
                                    @if ($fila['cumplimiento'] === null)
                                        <span class="text-slate-300">—</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                            @if ($fila['cumplimiento'] >= 90) bg-emerald-50 text-emerald-700
                                            @elseif ($fila['cumplimiento'] >= 70) bg-amber-50 text-amber-700
                                            @else bg-rose-50 text-rose-700 @endif">{{ $fila['cumplimiento'] }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">Sin datos en el periodo.</td></tr>
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
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><x-icon name="alert" class="w-5 h-5" /></span>
                <h2 class="text-sm font-semibold text-slate-700">Tareas vencidas</h2>
            </div>
            @forelse ($vencidas as $t)
                <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                   class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 -mx-1 transition hover:bg-rose-50/60">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-rose-700">{{ $t->titulo }}</p>
                        <p class="text-[11px] text-slate-400">{{ $t->asignado?->name ?? 'Sin asignar' }} · {{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700">{{ $t->fecha_limite->diffForHumans() }}</span>
                </a>
            @empty
                <div class="py-6 text-center text-sm text-slate-400">Ninguna tarea vencida. 🎉</div>
            @endforelse
        </x-card>

        <x-card>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><x-icon name="clock" class="w-5 h-5" /></span>
                <h2 class="text-sm font-semibold text-slate-700">Proximas a vencer</h2>
            </div>
            @forelse ($proximasVencer as $t)
                <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                   class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 -mx-1 transition hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-indigo-700">{{ $t->titulo }}</p>
                        <p class="text-[11px] text-slate-400">{{ $t->asignado?->name ?? 'Sin asignar' }} · {{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $t->fecha_limite->diffForHumans() }}</span>
                </a>
            @empty
                <div class="py-6 text-center text-sm text-slate-400">Nada pendiente por vencer.</div>
            @endforelse
        </x-card>
    </div>
</div>
