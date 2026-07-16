<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Encabezado + selector de periodo --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Cumplimiento y SLA</h1>
                <p class="text-sm text-gray-500">Indicadores de actividades y proyectos de tecnologia.</p>
            </div>
            <select wire:model.live="rango"
                    class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($this->periodos() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tarjetas KPI --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Cumplimiento --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">% Cumplimiento SLA</p>
                <div class="mt-2 flex items-end gap-2">
                    <span class="text-3xl font-bold
                        @if ($resumen['cumplimiento'] >= 90) text-emerald-600
                        @elseif ($resumen['cumplimiento'] >= 70) text-amber-600
                        @else text-rose-600 @endif">
                        {{ $resumen['cumplimiento'] }}%
                    </span>
                </div>
                <div class="mt-3 h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full
                        @if ($resumen['cumplimiento'] >= 90) bg-emerald-500
                        @elseif ($resumen['cumplimiento'] >= 70) bg-amber-500
                        @else bg-rose-500 @endif"
                        style="width: {{ min($resumen['cumplimiento'], 100) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-400">{{ $resumen['a_tiempo'] }} de {{ $resumen['completadas'] }} completadas a tiempo</p>
            </div>

            {{-- Abiertas vencidas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Tareas vencidas (abiertas)</p>
                <p class="mt-2 text-3xl font-bold text-rose-600">{{ $resumen['abiertas_vencidas'] }}</p>
                <p class="mt-3 text-xs text-gray-400">{{ $resumen['abiertas'] }} tareas abiertas en total</p>
            </div>

            {{-- Tiempo promedio --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Tiempo promedio resolucion</p>
                <p class="mt-2 text-3xl font-bold text-indigo-600">
                    {{ $resumen['tiempo_promedio'] !== null ? $resumen['tiempo_promedio'].'h' : '—' }}
                </p>
                <p class="mt-3 text-xs text-gray-400">En el periodo seleccionado</p>
            </div>

            {{-- Completadas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Completadas / Vencidas cerradas</p>
                <p class="mt-2 text-3xl font-bold text-gray-800">
                    {{ $resumen['completadas'] }}
                    <span class="text-lg text-rose-500">/ {{ $resumen['vencidas_cerradas'] }}</span>
                </p>
                <p class="mt-3 text-xs text-gray-400">Total asignadas: {{ $resumen['total'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Cumplimiento por tipo --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Cumplimiento por tipo</h2>
                <div class="space-y-4">
                    @foreach ($porTipo as $fila)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="capitalize text-gray-600">{{ $fila['tipo'] }}</span>
                                <span class="text-gray-500">
                                    {{ $fila['cumplimiento'] }}%
                                    <span class="text-gray-400">({{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }})</span>
                                </span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full
                                    @if ($fila['cumplimiento'] >= 90) bg-emerald-500
                                    @elseif ($fila['cumplimiento'] >= 70) bg-amber-500
                                    @else bg-rose-500 @endif"
                                    style="width: {{ min($fila['cumplimiento'], 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $fila['abiertas'] }} abiertas</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Ranking por persona --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Cumplimiento por persona</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b">
                                <th class="py-2 pr-2 font-medium">Empleado</th>
                                <th class="py-2 px-2 font-medium text-center">A tiempo</th>
                                <th class="py-2 px-2 font-medium text-center">Abiertas</th>
                                <th class="py-2 px-2 font-medium text-center">Vencidas</th>
                                <th class="py-2 pl-2 font-medium text-right">Cumpl.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($porPersona as $fila)
                                <tr>
                                    <td class="py-2 pr-2">
                                        <span class="font-medium text-gray-700">{{ $fila['usuario']->name }}</span>
                                        <span class="block text-xs text-gray-400 capitalize">{{ $fila['usuario']->area }}</span>
                                    </td>
                                    <td class="py-2 px-2 text-center text-gray-600">{{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }}</td>
                                    <td class="py-2 px-2 text-center text-gray-600">{{ $fila['abiertas'] }}</td>
                                    <td class="py-2 px-2 text-center {{ $fila['vencidas'] > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">{{ $fila['vencidas'] }}</td>
                                    <td class="py-2 pl-2 text-right font-semibold
                                        @if ($fila['cumplimiento'] === null) text-gray-300
                                        @elseif ($fila['cumplimiento'] >= 90) text-emerald-600
                                        @elseif ($fila['cumplimiento'] >= 70) text-amber-600
                                        @else text-rose-600 @endif">
                                        {{ $fila['cumplimiento'] !== null ? $fila['cumplimiento'].'%' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-gray-400">Sin datos en el periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Vencidas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-rose-700 mb-4">⚠ Tareas vencidas</h2>
                @forelse ($vencidas as $t)
                    <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                       class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ $t->titulo }}</p>
                            <p class="text-xs text-gray-400">{{ $t->asignado?->name ?? 'Sin asignar' }} · {{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</p>
                        </div>
                        <span class="text-xs text-rose-600 whitespace-nowrap ml-2">
                            {{ $t->fecha_limite->diffForHumans() }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">Ninguna tarea vencida. 🎉</p>
                @endforelse
            </div>

            {{-- Proximas a vencer --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Proximas a vencer</h2>
                @forelse ($proximasVencer as $t)
                    <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                       class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ $t->titulo }}</p>
                            <p class="text-xs text-gray-400">{{ $t->asignado?->name ?? 'Sin asignar' }} · {{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</p>
                        </div>
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                            {{ $t->fecha_limite->diffForHumans() }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">Nada pendiente por vencer.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
