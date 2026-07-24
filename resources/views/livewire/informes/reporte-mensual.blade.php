<div class="p-4 sm:p-6 lg:p-8 anim-fade-up">
    {{-- Estilos de impresion: ocultar navegacion y controles (siempre en claro) --}}
    <style>
        @media print {
            aside, .lg\:hidden, .no-print { display: none !important; }
            .lg\:pl-64 { padding-left: 0 !important; }
            html, body { background: white !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; background: white !important; color: #1e293b !important; }
        }
    </style>

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Encabezado + controles --}}
        <x-page-header title="Reporte de Cumplimiento" icon="report">
            <x-slot:subtitle>Periodo: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $this->etiquetaMes }}</span></x-slot:subtitle>
            <x-slot:actions>
                <div class="flex items-center gap-2 no-print">
                    <input type="month" wire:model.live="mes"
                           class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <button wire:click="exportarCsv"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 dark:border-emerald-500/40 bg-emerald-50 dark:bg-emerald-500/10 px-3 py-2.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 active:scale-[0.98] transition">
                        <x-icon name="download" class="w-4 h-4" /> Excel/CSV
                    </button>
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.98] transition">
                        <x-icon name="print" class="w-4 h-4" /> Imprimir / PDF
                    </button>
                </div>
            </x-slot:actions>
        </x-page-header>

        {{-- Resumen ejecutivo --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm dark:shadow-black/20 border border-gray-100 dark:border-slate-800 p-5 print-card">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-4">Resumen ejecutivo</h2>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Cumplimiento SLA</p>
                    <p class="text-2xl font-bold
                        @if ($resumen['cumplimiento'] >= 90) text-emerald-600 dark:text-emerald-400
                        @elseif ($resumen['cumplimiento'] >= 70) text-amber-600 dark:text-amber-400
                        @else text-rose-600 dark:text-rose-400 @endif">{{ $resumen['cumplimiento'] }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Tareas completadas</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-slate-100">{{ $resumen['completadas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Fuera de SLA</p>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $resumen['vencidas_cerradas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Abiertas vencidas</p>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $resumen['abiertas_vencidas'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Tiempo prom. resol.</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $resumen['tiempo_promedio'] !== null ? $resumen['tiempo_promedio'].'h' : '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Cumplimiento por proyecto --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm dark:shadow-black/20 border border-gray-100 dark:border-slate-800 p-5 print-card">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-4">Cumplimiento por proyecto</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-800">
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
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                        @forelse ($porProyecto as $r)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-700 dark:text-slate-200">{{ $r['proyecto']->nombre }}</td>
                                <td class="py-2 px-3 text-gray-600 dark:text-slate-400">{{ $r['proyecto']->responsable?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-center text-gray-600 dark:text-slate-400">{{ $r['total'] }}</td>
                                <td class="py-2 px-3 text-center text-gray-600 dark:text-slate-400">{{ $r['a_tiempo'] }}/{{ $r['completadas'] }}</td>
                                <td class="py-2 px-3 text-center text-gray-600 dark:text-slate-400">{{ $r['abiertas'] }}</td>
                                <td class="py-2 px-3 text-center {{ $r['vencidas'] > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-gray-400 dark:text-slate-600' }}">{{ $r['vencidas'] }}</td>
                                <td class="py-2 px-3 text-center text-gray-600 dark:text-slate-400">{{ $r['progreso'] }}%</td>
                                <td class="py-2 pl-3 text-right font-semibold
                                    @if ($r['cumplimiento'] === null) text-gray-300 dark:text-slate-600
                                    @elseif ($r['cumplimiento'] >= 90) text-emerald-600 dark:text-emerald-400
                                    @elseif ($r['cumplimiento'] >= 70) text-amber-600 dark:text-amber-400
                                    @else text-rose-600 dark:text-rose-400 @endif">
                                    {{ $r['cumplimiento'] !== null ? $r['cumplimiento'].'%' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-4 text-center text-gray-400 dark:text-slate-500">Sin actividad de proyectos en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Por persona --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm dark:shadow-black/20 border border-gray-100 dark:border-slate-800 p-5 print-card">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-4">Cumplimiento por persona</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-gray-500 dark:text-slate-400 border-b border-gray-200 dark:border-slate-800">
                            <tr class="text-left">
                                <th class="py-2 pr-3 font-medium">Empleado</th>
                                <th class="py-2 px-3 font-medium text-center">A tiempo</th>
                                <th class="py-2 px-3 font-medium text-center">Vencidas</th>
                                <th class="py-2 pl-3 font-medium text-right">Cumpl.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                            @forelse ($porPersona as $r)
                                <tr>
                                    <td class="py-2 pr-3">
                                        <span class="font-medium text-gray-700 dark:text-slate-200">{{ $r['usuario']->name }}</span>
                                        <span class="block text-xs text-gray-400 dark:text-slate-500">{{ $r['usuario']->subDepartamentoNombre() }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-center text-gray-600 dark:text-slate-400">{{ $r['a_tiempo'] }}/{{ $r['completadas'] }}</td>
                                    <td class="py-2 px-3 text-center {{ $r['vencidas'] > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-gray-400 dark:text-slate-600' }}">{{ $r['vencidas'] }}</td>
                                    <td class="py-2 pl-3 text-right font-semibold
                                        @if ($r['cumplimiento'] === null) text-gray-300 dark:text-slate-600
                                        @elseif ($r['cumplimiento'] >= 90) text-emerald-600 dark:text-emerald-400
                                        @elseif ($r['cumplimiento'] >= 70) text-amber-600 dark:text-amber-400
                                        @else text-rose-600 dark:text-rose-400 @endif">
                                        {{ $r['cumplimiento'] !== null ? $r['cumplimiento'].'%' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-400 dark:text-slate-500">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Por tipo --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm dark:shadow-black/20 border border-gray-100 dark:border-slate-800 p-5 print-card">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-4">Cumplimiento por tipo de trabajo</h2>
                <div class="space-y-4">
                    @foreach ($porTipo as $fila)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="capitalize text-gray-600 dark:text-slate-300">{{ $fila['tipo'] }}</span>
                                <span class="text-gray-500 dark:text-slate-400">{{ $fila['cumplimiento'] }}% <span class="text-gray-400 dark:text-slate-500">({{ $fila['a_tiempo'] }}/{{ $fila['completadas'] }})</span></span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700
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

        <p class="text-xs text-gray-400 dark:text-slate-500 text-center pt-2">
            Generado el {{ now()->format('d/m/Y H:i') }} · Projects
        </p>
    </div>
</div>
