<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="Tareas / Actividades" subtitle="Gestiona y da seguimiento a todas las actividades" icon="tasks">
        <x-slot:actions>
            @if (auth()->user()->puedeCrearTarea())
                <button type="button" wire:click="abrirCrear"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    <x-icon name="plus" class="w-4 h-4" /> Nueva tarea
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('ok'))
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">{{ session('ok') }}</div>
    @endif

    {{-- Filtros --}}
    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por titulo..."
                   class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500">
            <select wire:model.live="estado" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_progreso">En progreso</option>
                <option value="en_revision">En revision</option>
                <option value="completada">Completada</option>
                <option value="cancelada">Cancelada</option>
            </select>
            <select wire:model.live="sub_department_id" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los subdepartamentos</option>
                @foreach ($subDepartamentos as $sd)
                    <option value="{{ $sd->id }}">{{ $sd->nombre }}</option>
                @endforeach
            </select>
            <select wire:model.live="asignado" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los empleados</option>
                @foreach ($empleados as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-3 text-sm text-slate-600 dark:text-slate-300 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <input type="checkbox" wire:model.live="soloVencidas"
                       class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-rose-600 focus:ring-rose-500">
                Solo vencidas
            </label>
        </div>
    </x-card>

    {{-- Tabla --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-sm dark:shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
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
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse ($tareas as $t)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-5">
                                <button type="button" wire:click="abrirEditar({{ $t->id }})"
                                   class="font-medium text-slate-800 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 text-left">{{ $t->titulo }}</button>
                                <span class="block text-xs text-slate-400 dark:text-slate-500">{{ $t->proyecto?->nombre ?? 'Sin proyecto' }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $t->asignado?->name ?? '—' }}</td>
                            <td class="py-3 px-4"><x-subdepartamento-badge :subdepartamento="$t->subDepartamento" /></td>
                            <td class="py-3 px-4"><x-badge tipo="prioridad" :valor="$t->prioridad" /></td>
                            <td class="py-3 px-4"><x-badge tipo="estado" :valor="$t->estado" /></td>
                            <td class="py-3 px-4">
                                @if ($t->fecha_limite)
                                    <span class="text-xs {{ $t->estaVencida() ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                                        {{ $t->fecha_limite->format('d/m/Y H:i') }}
                                        @if ($t->estaVencida()) · vencida @endif
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-right">
                                @if (auth()->user()->esAdmin())
                                    <div class="flex items-center justify-end gap-1">
                                        <x-row-action variant="eliminar" wire:click="eliminar({{ $t->id }})"
                                                      :confirm="'¿Eliminar la tarea &quot;'.$t->titulo.'&quot;? Esta acción no se puede deshacer.'"
                                                      label="Eliminar {{ $t->titulo }}" />
                                    </div>
                                @elseif (! in_array($t->estado, ['completada', 'cancelada']))
                                    <button wire:click="avanzar({{ $t->id }})"
                                            class="inline-flex items-center gap-1 rounded-lg bg-blue-50 dark:bg-blue-500/15 px-2.5 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/25 active:scale-[0.97] transition">
                                        @if ($t->estado === 'en_revision') <x-icon name="check" class="w-3.5 h-3.5" /> Completar
                                        @else Avanzar → @endif
                                    </button>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 dark:text-slate-500">No hay tareas con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $tareas->links() }}</div>

    <x-form-modal :show="$mostrarModal" :title="$editando ? 'Editar tarea' : 'Nueva tarea'" wire-close="cerrarModal" max-width="4xl">
        @if ($mostrarModal)
            <livewire:tareas.form-tarea :task="$editando" :project-id="$proyectoPreseleccionadoId" :en-modal="true" :key="'form-tarea-'.($editando?->id ?? 'nuevo')" />
        @endif
    </x-form-modal>
</div>
