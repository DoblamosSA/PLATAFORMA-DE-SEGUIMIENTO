<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <a href="{{ route('proyectos') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Proyectos</a>
                <h1 class="text-2xl font-bold text-gray-800 mt-1">{{ $project->nombre }}</h1>
                <div class="mt-2 flex flex-wrap gap-2">
                    <x-badge tipo="tipo" :valor="$project->tipo" />
                    <x-badge tipo="estado" :valor="$project->estado" />
                    <x-badge tipo="prioridad" :valor="$project->prioridad" />
                </div>
                @if ($project->descripcion)
                    <p class="mt-3 text-sm text-gray-500 max-w-2xl">{{ $project->descripcion }}</p>
                @endif
                <p class="mt-2 text-xs text-gray-400">Responsable: {{ $project->responsable?->name ?? 'Sin asignar' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tareas.crear', ['project' => $project->id]) }}" wire:navigate
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Asignar tarea
                </a>
                <a href="{{ route('proyectos.editar', $project) }}" wire:navigate
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Editar
                </a>
            </div>
        </div>

        {{-- KPIs de cumplimiento del proyecto --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Cumplimiento SLA</p>
                <p class="mt-2 text-3xl font-bold
                    @if ($metricas['cumplimiento'] >= 90) text-emerald-600
                    @elseif ($metricas['cumplimiento'] >= 70) text-amber-600
                    @else text-rose-600 @endif">{{ $metricas['cumplimiento'] }}%</p>
                <p class="mt-2 text-xs text-gray-400">{{ $metricas['a_tiempo'] }}/{{ $metricas['completadas'] }} a tiempo</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Progreso</p>
                <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $project->progreso }}%</p>
                <div class="mt-2 h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full bg-indigo-500" style="width: {{ $project->progreso }}%"></div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Tareas totales</p>
                <p class="mt-2 text-3xl font-bold text-gray-800">{{ $metricas['total'] }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ $metricas['completadas'] }} completadas</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Abiertas</p>
                <p class="mt-2 text-3xl font-bold text-blue-600">{{ $metricas['abiertas'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Vencidas</p>
                <p class="mt-2 text-3xl font-bold text-rose-600">{{ $metricas['vencidas'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Equipo + cumplimiento por desarrollador --}}
            <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700">Equipo ({{ count($equipo) }})</h2>
                    <a href="{{ route('proyectos.editar', $project) }}" wire:navigate class="text-xs text-indigo-600 hover:underline">Gestionar</a>
                </div>
                @forelse ($equipo as $m)
                    <div class="py-3 border-b border-gray-50 last:border-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">{{ $m['usuario']->name }}</p>
                                <p class="text-xs text-gray-400 capitalize">{{ $m['usuario']->pivot->rol_en_proyecto }}</p>
                            </div>
                            <span class="text-sm font-semibold
                                @if ($m['cumplimiento'] === null) text-gray-300
                                @elseif ($m['cumplimiento'] >= 90) text-emerald-600
                                @elseif ($m['cumplimiento'] >= 70) text-amber-600
                                @else text-rose-600 @endif">
                                {{ $m['cumplimiento'] !== null ? $m['cumplimiento'].'%' : '—' }}
                            </span>
                        </div>
                        <div class="mt-1 flex gap-3 text-xs text-gray-400">
                            <span>{{ $m['a_tiempo'] }}/{{ $m['completadas'] }} a tiempo</span>
                            <span>{{ $m['abiertas'] }} abiertas</span>
                            @if ($m['vencidas'] > 0)
                                <span class="text-rose-500 font-medium">{{ $m['vencidas'] }} vencidas</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Sin equipo asignado.
                        <a href="{{ route('proyectos.editar', $project) }}" wire:navigate class="text-indigo-600 hover:underline">Agregar desarrolladores</a>
                    </p>
                @endforelse
            </div>

            {{-- Tareas del proyecto --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 pb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Tareas del proyecto</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr class="text-left">
                                <th class="py-2 px-4 font-medium">Tarea</th>
                                <th class="py-2 px-4 font-medium">Asignado</th>
                                <th class="py-2 px-4 font-medium">Estado</th>
                                <th class="py-2 px-4 font-medium">Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($tareas as $t)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4">
                                        <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                                           class="font-medium text-gray-800 hover:text-indigo-600">{{ $t->titulo }}</a>
                                        <div class="mt-0.5"><x-badge tipo="prioridad" :valor="$t->prioridad" /></div>
                                    </td>
                                    <td class="py-2 px-4 text-gray-600">{{ $t->asignado?->name ?? '—' }}</td>
                                    <td class="py-2 px-4"><x-badge tipo="estado" :valor="$t->estado" /></td>
                                    <td class="py-2 px-4">
                                        @if ($t->fecha_limite)
                                            <span class="text-xs {{ $t->estaVencida() ? 'text-rose-600 font-semibold' : 'text-gray-500' }}">
                                                {{ $t->fecha_limite->format('d/m/Y H:i') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-gray-400">
                                    Este proyecto aun no tiene tareas.
                                    <a href="{{ route('tareas.crear', ['project' => $project->id]) }}" wire:navigate class="text-indigo-600 hover:underline">Asignar la primera</a>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
