<div class="p-4 sm:p-6 lg:p-8 anim-fade-up">
    <div class="max-w-7xl mx-auto space-y-6">

        <x-page-header title="Evaluador de colaboradores" icon="trend">
            <x-slot:subtitle>
                Historial de desempeño acumulado
            </x-slot:subtitle>
        </x-page-header>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Departamento</label>
                <select wire:model.live="department_id"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm">
                    <option value="">Todos</option>
                    @foreach ($departamentos as $d)
                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Colaborador</label>
                <select wire:model.live="colaborador_id"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-sm">
                    <option value="">Todos</option>
                    @foreach ($colaboradoresFiltro as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr class="text-left">
                            <th class="py-3 px-4 font-medium">#</th>
                            <th class="py-3 px-4 font-medium">Colaborador</th>
                            <th class="py-3 px-4 font-medium text-right">Carga</th>
                            <th class="py-3 px-4 font-medium text-right">Capacidad</th>
                            <th class="py-3 px-4 font-medium text-center">Tareas</th>
                            <th class="py-3 px-4 font-medium text-center">A tiempo</th>
                            <th class="py-3 px-4 font-medium text-center">Tarde</th>
                            <th class="py-3 px-4 font-medium text-center">Vencidas</th>
                            <th class="py-3 px-4 font-medium text-center">Pend.</th>
                            <th class="py-3 px-4 font-medium text-right">Cumpl.</th>
                            <th class="py-3 px-4 font-medium text-right">Puntaje</th>
                            <th class="py-3 px-4 font-medium">Clasificación</th>
                        </tr>
                    </thead>
                        @forelse ($ranking as $fila)
                            @php $color = $fila['clasificacion']['color']; @endphp
                            <tbody x-data="{ open: false }" class="border-b border-slate-100 dark:border-slate-800">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="py-3 px-4 font-semibold text-slate-500">{{ $fila['posicion'] }}</td>
                                <td class="py-3 px-4">
                                    <button type="button" @click="open = !open" class="text-left font-medium text-slate-800 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $fila['usuario']->name }}
                                    </button>
                                    <p class="text-[11px] text-slate-400">{{ $fila['usuario']->subDepartamentoNombre() }}</p>
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums">{{ rtrim(rtrim(number_format($fila['carga_asignada'], 2), '0'), '.') }} h</td>
                                <td class="py-3 px-4 text-right tabular-nums">{{ rtrim(rtrim(number_format($fila['capacidad_disponible'], 2), '0'), '.') }} h</td>
                                <td class="py-3 px-4 text-center">{{ $fila['tareas_asignadas'] }}</td>
                                <td class="py-3 px-4 text-center text-emerald-600 dark:text-emerald-400">{{ $fila['finalizadas_a_tiempo'] }}</td>
                                <td class="py-3 px-4 text-center text-amber-600 dark:text-amber-400">{{ $fila['finalizadas_tarde'] }}</td>
                                <td class="py-3 px-4 text-center text-rose-600 dark:text-rose-400">{{ $fila['vencidas'] }}</td>
                                <td class="py-3 px-4 text-center">{{ $fila['pendientes'] }}</td>
                                <td class="py-3 px-4 text-right tabular-nums">{{ $fila['porcentaje_cumplimiento'] }}%</td>
                                <td class="py-3 px-4 text-right font-semibold tabular-nums
                                    @if ($color === 'emerald') text-emerald-600 dark:text-emerald-400
                                    @elseif ($color === 'amber') text-amber-600 dark:text-amber-400
                                    @else text-rose-600 dark:text-rose-400 @endif">{{ $fila['puntaje'] }}%</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if ($color === 'emerald') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300
                                        @elseif ($color === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300
                                        @else bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 @endif">
                                        {{ $fila['clasificacion']['etiqueta'] }}
                                    </span>
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak class="bg-slate-50/70 dark:bg-slate-800/30">
                                <td colspan="12" class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                    <p class="font-medium mb-1">Desglose del puntaje</p>
                                    <p>{{ $fila['desglose']['formula'] }} = <strong>{{ $fila['puntaje'] }}%</strong></p>
                                    <ul class="mt-1 list-disc pl-5 space-y-0.5">
                                        <li>Puntualidad (a tiempo / asignadas): {{ $fila['desglose']['puntualidad'] }}% × {{ $fila['desglose']['peso_puntualidad'] }}</li>
                                        <li>Sin vencidas: {{ $fila['desglose']['sin_vencidas'] }}% × {{ $fila['desglose']['peso_sin_vencidas'] }}</li>
                                        <li>Carga completada (h cerradas / h asignadas): {{ $fila['desglose']['carga_completada'] }}% × {{ $fila['desglose']['peso_carga_completada'] }}</li>
                                    </ul>
                                </td>
                            </tr>
                            </tbody>
                        @empty
                            <tbody>
                            <tr>
                                <td colspan="12" class="py-8 text-center text-slate-400">No hay colaboradores para los filtros seleccionados.</td>
                            </tr>
                            </tbody>
                        @endforelse
                    </table>
            </div>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500">
            Carga/Capacidad reflejan la semana actual (solo tareas abiertas). Tareas, A tiempo, Tarde, Vencidas, Pend.,
            Cumpl. y Puntaje son históricos: consideran todas las tareas que el colaborador ha tenido asignadas
            alguna vez (excluye canceladas). Pesos configurables en <code>config/operativa.php</code>.
            Empate: menos vencidas → mayor % a tiempo → nombre.
        </p>
    </div>
</div>
