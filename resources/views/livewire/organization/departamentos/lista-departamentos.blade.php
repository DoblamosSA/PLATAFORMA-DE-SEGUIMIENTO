<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="Departamentos" subtitle="Estructura organizacional de la empresa" icon="building">
        <x-slot:actions>
            <button type="button" wire:click="abrirCrear"
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                <x-icon name="plus" class="w-4 h-4" /> Nuevo departamento
            </button>
        </x-slot:actions>
    </x-page-header>

    @if (session('ok'))
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">{{ session('ok') }}</div>
    @endif

    <x-card>
        <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre..."
               aria-label="Buscar departamento"
               class="w-full sm:max-w-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500">
    </x-card>

    <div class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    <tr class="text-left">
                        <th class="py-2.5 px-5 font-medium">Departamento</th>
                        <th class="py-2.5 px-4 font-medium">Responsable</th>
                        <th class="py-2.5 px-4 font-medium">SubDepartamentos</th>
                        <th class="py-2.5 px-4 font-medium">Usuarios</th>
                        <th class="py-2.5 px-4 font-medium">Estado</th>
                        <th class="py-2.5 px-5 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse ($departamentos as $d)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-2.5 px-5">
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $d->nombre }}</p>
                                @if ($d->descripcion)
                                    <p class="text-xs text-slate-400 dark:text-slate-500 truncate max-w-xs">{{ $d->descripcion }}</p>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">{{ $d->responsable?->name ?? '— sin asignar —' }}</td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300 tabular-nums">{{ $d->sub_departments_count }}</td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300 tabular-nums">{{ $d->users_count }}</td>
                            <td class="py-2.5 px-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium
                                             {{ $d->activo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $d->activo ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $d->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <x-row-action variant="editar" wire:click="abrirEditar({{ $d->id }})" label="Editar {{ $d->nombre }}" />
                                    <x-row-action variant="eliminar" wire:click="eliminar({{ $d->id }})"
                                                  :confirm="'¿Eliminar el departamento &quot;'.$d->nombre.'&quot;? Esta acción no se puede deshacer.'"
                                                  label="Eliminar {{ $d->nombre }}" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state icon="building" mensaje="No hay departamentos todavía.">
                                    <button type="button" wire:click="abrirCrear" class="text-blue-600 dark:text-blue-400 hover:underline">Crear el primero</button>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $departamentos->links() }}</div>

    <x-form-modal :show="$mostrarModal" :title="$editando ? 'Editar departamento' : 'Nuevo departamento'" wire-close="cerrarModal" max-width="2xl">
        @if ($mostrarModal)
            <livewire:organization.departamentos.form-departamento :department="$editando" :en-modal="true" :key="'form-departamento-'.($editando?->id ?? 'nuevo')" />
        @endif
    </x-form-modal>
</div>
