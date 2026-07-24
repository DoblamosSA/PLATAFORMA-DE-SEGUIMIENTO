<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="Roles" subtitle="Roles primarios (solo lectura) y roles heredados" icon="shield-check">
        <x-slot:actions>
            <button type="button" wire:click="abrirCrear"
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                <x-icon name="plus" class="w-4 h-4" /> Nuevo rol heredado
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre..."
               aria-label="Buscar rol"
               class="w-full sm:max-w-sm rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500">
    </x-card>

    <div class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    <tr class="text-left">
                        <th class="py-2.5 px-5 font-medium">Rol</th>
                        <th class="py-2.5 px-4 font-medium">Origen</th>
                        <th class="py-2.5 px-4 font-medium">Hereda de</th>
                        <th class="py-2.5 px-4 font-medium">Departamento</th>
                        <th class="py-2.5 px-5 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse ($roles as $r)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-2.5 px-5 font-medium text-slate-800 dark:text-slate-100">{{ $r->nombre }}</td>
                            <td class="py-2.5 px-4">
                                <x-badge tipo="rol_origen" :valor="$r->is_primary ? 'primario' : 'heredado'" />
                            </td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">{{ $r->parent->nombre ?? '—' }}</td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">{{ $r->department->nombre ?? '—' }}</td>
                            <td class="py-2.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($r->is_primary)
                                        @if (auth()->user()->esSuperAdmin())
                                            <x-row-action variant="editar" wire:click="abrirEditar({{ $r->id }})" label="Editar {{ $r->nombre }}" />
                                        @else
                                            <x-row-action variant="ver" wire:click="abrirEditar({{ $r->id }})" label="Ver permisos de {{ $r->nombre }}" />
                                        @endif
                                    @else
                                        <x-row-action variant="editar" wire:click="abrirEditar({{ $r->id }})" label="Editar {{ $r->nombre }}" />
                                        <x-row-action variant="duplicar" wire:click="duplicar({{ $r->id }})" label="Duplicar {{ $r->nombre }}" />
                                        @if ($r->is_deletable)
                                            <x-row-action variant="eliminar" wire:click="eliminar({{ $r->id }})"
                                                          :confirm="'¿Eliminar el rol &quot;'.$r->nombre.'&quot;? Esta acción no se puede deshacer.'"
                                                          label="Eliminar {{ $r->nombre }}" />
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="shield-check" mensaje="No hay roles todavía." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $roles->links() }}</div>

    <x-form-modal :show="$mostrarModal" :title="$editando?->is_primary ? ((auth()->user()->esSuperAdmin() ? 'Editar rol: ' : 'Ver rol: ').$editando->nombre) : ($editando ? 'Editar rol' : 'Nuevo rol heredado')" wire-close="cerrarModal" max-width="4xl">
        @if ($mostrarModal)
            <livewire:organization.roles.form-role :role="$editando" :en-modal="true" :key="'form-rol-'.($editando?->id ?? 'nuevo')" />
        @endif
    </x-form-modal>
</div>
