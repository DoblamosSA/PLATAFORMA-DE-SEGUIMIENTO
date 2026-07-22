<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-4xl mx-auto space-y-5 anim-fade-up">

        <x-page-header :title="$soloLectura ? 'Ver rol: '.$nombre : ($role ? 'Editar rol' : 'Nuevo rol heredado')"
                        subtitle="Herencia y permisos" icon="shield-check">
            <x-slot:actions>
                <a href="{{ route('roles') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                    <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                </a>
            </x-slot:actions>
        </x-page-header>

        @if ($soloLectura)
            <div class="rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50 dark:bg-indigo-500/10 px-4 py-3 text-sm text-indigo-700 dark:text-indigo-400">
                Este es un rol primario: es de solo lectura y no puede editarse ni eliminarse.
            </div>
        @endif

        <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nombre *</label>
                    <input id="nombre" type="text" wire:model="nombre" @disabled($soloLectura)
                           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm disabled:opacity-60 focus:border-blue-500 focus:ring-blue-500">
                    @error('nombre') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="parent_role_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Rol padre *</label>
                    <select id="parent_role_id" wire:model.live="parent_role_id" @disabled($soloLectura)
                            class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm disabled:opacity-60">
                        <option value="">Selecciona un rol</option>
                        @foreach ($rolesPadre as $rp)
                            <option value="{{ $rp->id }}">{{ $rp->nombre }}</option>
                        @endforeach
                    </select>
                    @error('parent_role_id') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Departamento *</label>
                    <select id="department_id" wire:model="department_id" @disabled($soloLectura)
                            class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm disabled:opacity-60">
                        <option value="">Selecciona un departamento</option>
                        @foreach ($departamentos as $d)
                            <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Permisos</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></span> Heredado</span>
                        &nbsp;·&nbsp;
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Agregado</span>
                        &nbsp;·&nbsp;
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Quitado</span>
                    </p>
                </div>

                @foreach ($gruposPermisos as $grupo => $permisos)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <p class="bg-slate-50 dark:bg-slate-800/60 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ucfirst($grupo ?? 'general') }}</p>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($permisos as $permiso)
                                @php $heredado = in_array($permiso->slug, $this->permisosHeredados, true); @endphp
                                <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                                    <div class="min-w-0">
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ $permiso->nombre }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $permiso->slug }}</p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0 text-xs" role="radiogroup" aria-label="Estado de {{ $permiso->nombre }}">
                                        <label class="inline-flex items-center gap-1 cursor-pointer {{ $heredado ? 'text-slate-500 dark:text-slate-400' : 'text-slate-300 dark:text-slate-600' }}">
                                            <input type="radio" wire:model="overrides.{{ $permiso->id }}" value="heredado" @disabled($soloLectura)>
                                            {{ $heredado ? 'Heredado ✓' : 'Heredado' }}
                                        </label>
                                        <label class="inline-flex items-center gap-1 cursor-pointer text-emerald-600 dark:text-emerald-400">
                                            <input type="radio" wire:model="overrides.{{ $permiso->id }}" value="grant" @disabled($soloLectura)>
                                            Agregar
                                        </label>
                                        <label class="inline-flex items-center gap-1 cursor-pointer text-rose-600 dark:text-rose-400">
                                            <input type="radio" wire:model="overrides.{{ $permiso->id }}" value="deny" @disabled($soloLectura)>
                                            Quitar
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @unless ($soloLectura)
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                        {{ $role ? 'Guardar cambios' : 'Crear rol' }}
                    </button>
                    <a href="{{ route('roles') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>
                </div>
            @endunless
        </form>
    </div>
</div>
