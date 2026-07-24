<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="Colaboradores" subtitle="Equipos, roles y capacidad operativa" icon="users">
        <x-slot:actions>
            <a href="{{ route('colaboradores.crear') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                <x-icon name="plus" class="w-4 h-4" /> Nuevo colaborador
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filtros --}}
    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre o correo..."
                   aria-label="Buscar colaborador"
                   class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500">
            <select wire:model.live="rol" aria-label="Filtrar por rol" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los roles</option>
                @foreach (\App\Models\User::ROLES_LABEL as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <select wire:model.live="area" aria-label="Filtrar por área" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todas las áreas</option>
                <option value="software">Software</option>
                <option value="soporte">Soporte</option>
                <option value="infraestructura">Infraestructura</option>
                <option value="general">General</option>
            </select>
        </div>
    </x-card>

    <div class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    <tr class="text-left">
                        <th class="py-2.5 px-5 font-medium">Colaborador</th>
                        <th class="py-2.5 px-4 font-medium">Rol</th>
                        <th class="py-2.5 px-4 font-medium">Departamento</th>
                        <th class="py-2.5 px-4 font-medium">Capacidad semanal</th>
                        <th class="py-2.5 px-4 font-medium w-48">Carga (semana actual)</th>
                        <th class="py-2.5 px-4 font-medium">Activo</th>
                        <th class="py-2.5 px-5 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse ($colaboradores as $c)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-2.5 px-5">
                                <div class="flex items-center gap-3">
                                    <x-avatar :usuario="$c" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ $c->name }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 truncate">{{ $c->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-2.5 px-4"><x-badge tipo="rol" :valor="$c->rol" /></td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300">
                                {{ $c->departments->first()?->nombre ?? '—' }}
                            </td>
                            <td class="py-2.5 px-4 text-slate-600 dark:text-slate-300 tabular-nums">
                                {{ $c->dias_laborales ? count($c->dias_laborales) . 'd × ' . rtrim(rtrim(number_format((float) $c->horas_diarias, 2), '0'), '.') . 'h = ' . rtrim(rtrim(number_format($c->capacidadSemanal(), 2), '0'), '.') . ' h' : '— sin definir' }}
                            </td>
                            <td class="py-2.5 px-4">
                                <x-carga-bar :porcentaje="$c->carga['porcentaje']" :estado="$c->carga['estado']" />
                            </td>
                            <td class="py-2.5 px-4">
                                <button wire:click="toggleActivo({{ $c->id }})"
                                        aria-pressed="{{ $c->activo ? 'true' : 'false' }}"
                                        aria-label="{{ $c->activo ? 'Desactivar' : 'Activar' }} a {{ $c->name }}"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500
                                               {{ $c->activo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $c->activo ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $c->activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="py-2.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <x-row-action variant="editar" :href="route('colaboradores.editar', $c)" label="Editar {{ $c->name }}" />
                                    @if ($c->id !== auth()->id() && ! $c->esSuperAdmin())
                                        <x-row-action variant="eliminar" wire:click="eliminar({{ $c->id }})"
                                                      :confirm="'¿Eliminar a &quot;'.$c->name.'&quot;? Esta acción no se puede deshacer.'"
                                                      label="Eliminar {{ $c->name }}" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 dark:text-slate-500">
                                No hay colaboradores con estos filtros.
                                <a href="{{ route('colaboradores.crear') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Crear el primero</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $colaboradores->links() }}</div>
</div>
