<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="SubDepartamentos" subtitle="Divisiones dentro de cada departamento" icon="sitemap">
        <x-slot:actions>
            <a href="{{ route('subdepartamentos.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                <x-icon name="plus" class="w-4 h-4" /> Nuevo subdepartamento
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('ok'))
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">{{ session('ok') }}</div>
    @endif

    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre..."
                   aria-label="Buscar subdepartamento"
                   class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500">
            <select wire:model.live="departamento" aria-label="Filtrar por departamento" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los departamentos</option>
                @foreach ($departamentos as $d)
                    <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                @endforeach
            </select>
        </div>
    </x-card>

    <div class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    <tr class="text-left">
                        <th class="py-2.5 px-5 font-medium">Subdepartamento</th>
                        <th class="py-2.5 px-4 font-medium">Departamento</th>
                        <th class="py-2.5 px-4 font-medium">Usuarios</th>
                        <th class="py-2.5 px-4 font-medium">Estado</th>
                        <th class="py-2.5 px-5 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse ($subDepartamentos as $sd)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-2.5 px-5">
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $sd->nombre }}</p>
                                @if ($sd->descripcion)
                                    <p class="text-xs text-slate-400 dark:text-slate-500 truncate max-w-xs">{{ $sd->descripcion }}</p>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">{{ $sd->department->nombre }}</td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300 tabular-nums">{{ $sd->users_count }}</td>
                            <td class="py-2.5 px-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium
                                             {{ $sd->activo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $sd->activo ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $sd->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-5 text-right space-x-3">
                                <a href="{{ route('subdepartamentos.editar', $sd) }}" wire:navigate
                                   class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">Editar</a>
                                <button wire:click="eliminar({{ $sd->id }})"
                                        wire:confirm="¿Eliminar el subdepartamento &quot;{{ $sd->nombre }}&quot;? Esta acción no se puede deshacer."
                                        class="text-xs font-medium text-rose-600 dark:text-rose-400 hover:underline">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="sitemap" mensaje="No hay subdepartamentos todavía.">
                                    <a href="{{ route('subdepartamentos.crear') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Crear el primero</a>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $subDepartamentos->links() }}</div>
</div>
