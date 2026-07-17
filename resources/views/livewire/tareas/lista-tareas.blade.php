<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    <x-page-header title="Tareas / Actividades" subtitle="Gestiona y da seguimiento a todas las actividades" icon="tasks">
        <x-slot:actions>
            <a href="{{ route('tareas.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 transition">
                <x-icon name="plus" class="w-4 h-4" /> Nueva tarea
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filtros --}}
    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por titulo..."
                   class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select wire:model.live="estado" class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_progreso">En progreso</option>
                <option value="en_revision">En revision</option>
                <option value="completada">Completada</option>
                <option value="cancelada">Cancelada</option>
            </select>
            <select wire:model.live="tipo" class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos los tipos</option>
                <option value="software">Software</option>
                <option value="soporte">Soporte</option>
                <option value="infraestructura">Infraestructura</option>
            </select>
            <select wire:model.live="asignado" class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos los empleados</option>
                @foreach ($empleados as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm text-slate-600 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model.live="soloVencidas"
                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                Solo vencidas
            </label>
        </div>
    </x-card>

    {{-- Tabla --}}
    <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-400">
                    <tr class="text-left">
                        <th class="py-3 px-5 font-medium">Tarea</th>
                        <th class="py-3 px-4 font-medium">Asignado</th>
                        <th class="py-3 px-4 font-medium">Tipo</th>
                        <th class="py-3 px-4 font-medium">Prioridad</th>
                        <th class="py-3 px-4 font-medium">Estado</th>
                        <th class="py-3 px-4 font-medium">Vencimiento</th>
                        <th class="py-3 px-5 font-medium text-right">Accion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($tareas as $t)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-5">
                                <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                                   class="font-medium text-slate-800 hover:text-indigo-600">{{ $t->titulo }}</a>
                                <span class="block text-xs text-slate-400">{{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $t->asignado?->name ?? '—' }}</td>
                            <td class="py-3 px-4"><x-badge tipo="tipo" :valor="$t->tipo" /></td>
                            <td class="py-3 px-4"><x-badge tipo="prioridad" :valor="$t->prioridad" /></td>
                            <td class="py-3 px-4"><x-badge tipo="estado" :valor="$t->estado" /></td>
                            <td class="py-3 px-4">
                                @if ($t->fecha_limite)
                                    <span class="text-xs {{ $t->estaVencida() ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                        {{ $t->fecha_limite->format('d/m/Y H:i') }}
                                        @if ($t->estaVencida()) · vencida @endif
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-right">
                                @if (! in_array($t->estado, ['completada', 'cancelada']))
                                    <button wire:click="avanzar({{ $t->id }})"
                                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 transition">
                                        @if ($t->estado === 'en_revision') <x-icon name="check" class="w-3.5 h-3.5" /> Completar
                                        @else Avanzar → @endif
                                    </button>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-400">No hay tareas con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $tareas->links() }}</div>
</div>
