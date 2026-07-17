<div class="py-8">
    {{-- Estilos de impresion: ocultar navegacion y controles --}}
    <style>
        @media print {
            nav, .no-print { display: none !important; }
            body { background: white !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; }
            main { padding: 0 !important; }
        }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Encabezado + controles --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Reporte de Cumplimiento</h1>
                <p class="text-sm text-gray-500">Periodo: <span class="font-medium text-gray-700">{{ $this->etiquetaMes }}</span></p>
            </div>
            <div class="flex items-center gap-2 no-print">
                <input type="month" wire:model.live="mes"
                       class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button wire:click="exportarCsv"
                        class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                    ⬇ Excel/CSV
                </button>
                <button onclick="window.print()"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    🖨 Imprimir / PDF
                </button>
            </div>
        </div>

        {{-- Resumen ejecutivo --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 print-card">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Resumen ejecutivo</h2>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Cumplimiento SLA</p>
                    <p class="text-2xl font-bold
                        @if ($resumen['cumplimiento'] >= 90) text-emerald-600
                        @elseif ($resumen['cumplimiento'] >= 70) text-amber-600
                        @else text-rose-600 @endif">{{ $resumen['cumplimiento'] }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Tareas completadas</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $resumen['completadas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Fuera de SLA</p>
                    <p class="text-2xl font-bold text-rose-600">{{ $resumen['vencidas_cerradas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Abiertas vencidas</p>
                    <p class="text-2xl font-bold text-rose-600">{{ $resumen['abiertas_vencidas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Tiempo prom. resol.</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $resumen['tiempo_promedio'] !== null ? $resumen['tiempo_promedio'].'h' : '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Cumplimiento por proyecto --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 print-card">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Cumplimiento por proyecto</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 border-b">
                        <tr class="text-left">
                            <th class="py-2 pr-3 font-medium">Proyecto</th>
                            <th class="py-2 px-3 font-medium">Responsable</th>
                            <th class="py-2 px-3 font-medium text-center">Tareas</th>
                            <th class="py-2 px-3 font-medium text-center">A tiempo</th>
                            <th class="py-2 px-3 font-medium text-center">Abiertas</th>
                            <th class="py-2 px-3 font-medium text-center">Vencidas</th>
                            <th class="py-2 px-3 font-medium text-center">Progreso</th>
                            <th class="py-2 pl-3 font-medium text-right">Cumpl.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($porProyecto as $r)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-700">{{ $r['proyecto']->nombre }}</td>
                                <td class="py-2 px-3 text-gray-600">{{ $r['proyecto']->responsable?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-center text-gray-600">{{ $r['total'] }}</td>
                                <td class="py-2 px-3 text-center text-gray-600">{{ $r['a_tiempo'] }}/{{ $r['completadas'] }}</td>
                                <td class="py-2 px-3 text-center text-gray-600">{{ $r['abiertas'] }}</td>
                                <td class="py-2 px-3 text-center {{ $r['vencidas'] > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">{{ $r['vencidas'] }}</td>
                                <td class="py-2 px-3 text-center text-gray-600">{{ $r['progreso'] }}%</td>
                                <td class="py-2 pl-3 text-right font-semibold
                                    @if ($r['cumplimiento'] === null) text-gray-300
                                    @elseif ($r['cumplimiento'] >= 90) text-emerald-600
                                    @elseif ($r['cumplimiento'] >= 70) text-amber-600
                                    @else text-rose-600 @endif">
                                    {{ $r['cumplimiento'] !== null ? $r['cumplimiento'].'%' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-4 text-center text-gray-400">Sin actividad de proyectos en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Por persona --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 print-card">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Cumplimiento por persona</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-500 border-b">
                            <tr class="text-left">
                                <th class="py-2 pr-3 font-medium">Empleado</th>
                                <th class="py-2 px-3 font-medium text-center">A tiempo</th>
                                <th class="py-2 px-3 font-medium text-center">Vencidas</th>
                                <th class="py-2 pl-3 font-medium text-right">Cumpl.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($porPersona as $r)
                                <tr>
                                    <td class="py-2 pr-3">
                                        <span class="font-medium text-gray-700">{{ $r['usuario']->name }}</span>
                                        <span class="block text-xs text-gray-400 capitalize">{{ $r['usuario']->area }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-center text-gray-600">{{ $r['a_tiempo'] }}/{{ $r['completadas'] }}</td>
                                    <td class="py-2 px-3 text-center {{ $r['vencidas'] > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">{{ $r['vencidas'] }}</td>
                                    <td class="py-2 pl-3 text-right font-semibold
                                        @if ($r['cumplimiento'] === null) text-gray-300
                                        @elseif ($r['cumplimiento'] >= 90) text-emerald-600
                                        @elseif ($r['cumplimiento'] >= 70) text-amber-600
                                        @else text-rose-600 @endif">
                                        {{ $r['cumplimiento'] !== null ? $r['cumplimiento'].'%' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-400">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Por tipo --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 print-card">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Cumplimiento por tipo de trabajo</h2>
                <div class="space-y-4">
                    @foreach ($porTipo as $fila)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="capitalize text-gray-600">{{ $fila['tipo'] }}</span>
                                <span class="text-gray-500">{{ $fila['cumplimiento'] }}% <span class="text-gray-400">({{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }})</span></span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full
                                    @if ($fila['cumplimiento'] >= 90) bg-emerald-500
                                    @elseif ($fila['cumplimiento'] >= 70) bg-amber-500
                                    @else bg-rose-500 @endif"
                                    style="width: {{ min($fila['cumplimiento'], 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-400 text-center pt-2">
            Generado el {{ now()->format('d/m/Y H:i') }} · Gestion TI
        </p>
    </div>
</div>
