<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        {{-- Encabezado --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Tareas / Actividades</h1>
            <a href="{{ route('tareas.crear') }}" wire:navigate
               class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Nueva tarea
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por titulo..."
                       class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select wire:model.live="estado" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_progreso">En progreso</option>
                    <option value="en_revision">En revision</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <select wire:model.live="tipo" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los tipos</option>
                    <option value="software">Software</option>
                    <option value="soporte">Soporte</option>
                    <option value="infraestructura">Infraestructura</option>
                </select>
                <select wire:model.live="asignado" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos los empleados</option>
                    @foreach ($empleados as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                    @endforeach
                </select>
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" wire:model.live="soloVencidas"
                           class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                    Solo vencidas
                </label>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr class="text-left">
                            <th class="py-3 px-4 font-medium">Tarea</th>
                            <th class="py-3 px-4 font-medium">Asignado</th>
                            <th class="py-3 px-4 font-medium">Tipo</th>
                            <th class="py-3 px-4 font-medium">Prioridad</th>
                            <th class="py-3 px-4 font-medium">Estado</th>
                            <th class="py-3 px-4 font-medium">Vencimiento</th>
                            <th class="py-3 px-4 font-medium text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($tareas as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <a href="{{ route('tareas.editar', $t) }}" wire:navigate
                                       class="font-medium text-gray-800 hover:text-indigo-600">{{ $t->titulo }}</a>
                                    <span class="block text-xs text-gray-400">{{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ $t->asignado?->name ?? '—' }}</td>
                                <td class="py-3 px-4"><x-badge tipo="tipo" :valor="$t->tipo" /></td>
                                <td class="py-3 px-4"><x-badge tipo="prioridad" :valor="$t->prioridad" /></td>
                                <td class="py-3 px-4"><x-badge tipo="estado" :valor="$t->estado" /></td>
                                <td class="py-3 px-4">
                                    @if ($t->fecha_limite)
                                        <span class="text-xs {{ $t->estaVencida() ? 'text-rose-600 font-semibold' : 'text-gray-500' }}">
                                            {{ $t->fecha_limite->format('d/m/Y H:i') }}
                                            @if ($t->estaVencida()) · vencida @endif
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if (! in_array($t->estado, ['completada', 'cancelada']))
                                        <button wire:click="avanzar({{ $t->id }})"
                                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                            @if ($t->estado === 'en_revision') ✓ Completar
                                            @else Avanzar → @endif
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-gray-400">No hay tareas con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $tareas->links() }}</div>
    </div>
</div>
