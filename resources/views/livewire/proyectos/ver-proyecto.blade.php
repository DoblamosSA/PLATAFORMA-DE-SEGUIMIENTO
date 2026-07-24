<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-fade-right">

    {{-- Encabezado --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <a href="{{ route('proyectos') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                <x-icon name="arrow-left" class="w-4 h-4" /> Proyectos
            </a>
            <div class="mt-2 flex items-center gap-3">
                @php $ico = $project->subDepartamento->icono ?? 'folder'; @endphp
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-sky-600 text-white shadow-lg shadow-blue-500/30">
                    <x-icon :name="$ico" class="w-6 h-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ $project->nombre }}</h1>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <x-subdepartamento-badge :subdepartamento="$project->subDepartamento" />
                        <x-badge tipo="estado" :valor="$project->estado" />
                        <x-badge tipo="prioridad" :valor="$project->prioridad" />
                    </div>
                </div>
            </div>
            @if ($project->descripcion)
                <p class="mt-3 max-w-2xl text-sm text-slate-500 dark:text-slate-400">{{ $project->descripcion }}</p>
            @endif
            <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">Responsable: {{ $project->responsable?->name ?? 'Sin asignar' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->puedeCrearTarea())
                <a href="{{ route('tareas.crear', ['project' => $project->id]) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    <x-icon name="plus" class="w-4 h-4" /> Asignar tarea
                </a>
            @endif
            @if ($project->usuarioPuedeGestionar(auth()->user()))
                <a href="{{ route('proyectos.tablero', $project) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-xl border border-blue-300 dark:border-blue-500/40 bg-blue-50 dark:bg-blue-500/10 px-4 py-2.5 text-sm font-medium text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 active:scale-[0.98] transition">
                    <x-icon name="tasks" class="w-4 h-4" /> Administrar tareas
                </a>
            @endif
            <button type="button" wire:click="abrirEditar"
               class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.98] transition">
                Editar
            </button>
            @if (auth()->user()->esSuperAdmin())
                <button x-on:click="$dispatch('confirm-modal', {
                            title: 'Eliminar',
                            message: @js('¿Eliminar el proyecto "'.$project->nombre.'"? Se eliminarán también sus tareas y su tablero. Esta acción no se puede deshacer.'),
                            confirmText: 'Eliminar',
                            danger: true,
                            onConfirm: () => $wire.eliminar(),
                        })"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 dark:border-rose-500/40 bg-rose-50 dark:bg-rose-500/10 px-4 py-2.5 text-sm font-medium text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-500/20 active:scale-[0.98] transition">
                    <x-icon name="trash" class="w-4 h-4" /> Eliminar
                </button>
            @endif
        </div>
    </div>

    {{-- Gauge + KPIs --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm dark:shadow-black/20">
            <div class="absolute inset-x-0 -top-px h-1 bg-gradient-to-r from-blue-500 via-sky-500 to-cyan-500"></div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 text-center">Cumplimiento del proyecto</p>
            <div class="mt-2"><x-gauge :value="$metricas['cumplimiento']" label="a tiempo" :size="150" /></div>
            <p class="text-center text-xs text-slate-400 dark:text-slate-500">{{ $metricas['a_tiempo'] }}/{{ $metricas['completadas'] }} a tiempo</p>
        </div>

        <div class="lg:col-span-3 grid grid-cols-2 lg:grid-cols-4 gap-5">
            <x-stat label="Progreso" :value="$project->progreso.'%'" icon="trend" tone="indigo" />
            <x-stat label="Tareas" :value="$metricas['total']" icon="tasks" tone="slate" hint="{{ $metricas['completadas'] }} completadas" />
            <x-stat label="Abiertas" :value="$metricas['abiertas']" icon="clock" tone="sky" />
            <x-stat label="Vencidas" :value="$metricas['vencidas']" icon="alert" tone="rose" />
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Equipo --}}
        <x-card class="lg:col-span-1">
            <x-slot:actions>
                <button type="button" wire:click="abrirEditar" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">Gestionar</button>
            </x-slot:actions>
            <x-slot:title>Equipo ({{ count($equipo) }})</x-slot:title>
            @forelse ($equipo as $m)
                @php $u = $m['usuario']; @endphp
                <div class="py-2.5 border-b border-slate-50 dark:border-slate-800 last:border-0">
                    <div class="flex items-center gap-3">
                        <x-avatar :usuario="$u" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $u->name }}</p>
                            <div class="flex gap-2 text-[11px] text-slate-400 dark:text-slate-500">
                                <span>{{ $m['a_tiempo'] }}/{{ $m['completadas'] }} a tiempo</span>
                                <span>·</span>
                                <span>{{ $m['abiertas'] }} abiertas</span>
                                @if ($m['vencidas'] > 0)<span class="text-rose-500 dark:text-rose-400 font-medium">· {{ $m['vencidas'] }} venc.</span>@endif
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-semibold
                            @if ($m['cumplimiento'] === null) text-slate-300 dark:text-slate-600
                            @elseif ($m['cumplimiento'] >= 90) text-emerald-600 dark:text-emerald-400
                            @elseif ($m['cumplimiento'] >= 70) text-amber-600 dark:text-amber-400
                            @else text-rose-600 dark:text-rose-400 @endif">
                            {{ $m['cumplimiento'] !== null ? $m['cumplimiento'].'%' : '—' }}
                        </span>
                    </div>
                    <div class="mt-2 pl-12">
                        <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 mb-0.5">
                            <span>Carga semanal</span>
                            <span class="tabular-nums">{{ $m['carga']['asignadas'] }} / {{ $m['carga']['disponibles'] ?: '—' }} h</span>
                        </div>
                        <x-carga-bar :porcentaje="$m['carga']['porcentaje']" :estado="$m['carga']['estado']" />
                    </div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-400 dark:text-slate-500">Sin equipo asignado.
                    <a href="{{ route('proyectos.editar', $project) }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Agregar desarrolladores</a>
                </p>
            @endforelse
        </x-card>

        {{-- Tareas --}}
        <div class="lg:col-span-2 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tareas del proyecto</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <tr class="text-left">
                            <th class="py-2.5 px-5 font-medium">Tarea</th>
                            <th class="py-2.5 px-4 font-medium">Asignado</th>
                            <th class="py-2.5 px-4 font-medium">Estado</th>
                            <th class="py-2.5 px-5 font-medium">Vence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse ($tareas as $t)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-5">
                                    <a @if (auth()->user()->puedeEditarTarea()) href="{{ route('tareas.editar', $t) }}" wire:navigate @endif
                                       class="font-medium text-slate-800 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400">{{ $t->titulo }}</a>
                                    <div class="mt-0.5"><x-badge tipo="prioridad" :valor="$t->prioridad" /></div>
                                </td>
                                <td class="py-2.5 px-4 text-slate-600 dark:text-slate-400">{{ $t->asignado?->name ?? '—' }}</td>
                                <td class="py-2.5 px-4"><x-badge tipo="estado" :valor="$t->estado" /></td>
                                <td class="py-2.5 px-5">
                                    @if ($t->fecha_limite)
                                        <span class="text-xs {{ $t->estaVencida() ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">{{ $t->fecha_limite->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                Este proyecto aun no tiene tareas.
                                @if (auth()->user()->puedeCrearTarea())
                                    <a href="{{ route('tareas.crear', ['project' => $project->id]) }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Asignar la primera</a>
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-form-modal :show="$mostrarModalEditar" title="Editar proyecto" wire-close="cerrarModalEditar" max-width="3xl">
        @if ($mostrarModalEditar)
            <livewire:proyectos.form-proyecto :project="$project" :en-modal="true" :key="'form-proyecto-'.$project->id" />
        @endif
    </x-form-modal>
</div>
