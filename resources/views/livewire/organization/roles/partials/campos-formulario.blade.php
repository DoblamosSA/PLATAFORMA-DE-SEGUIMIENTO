@if ($soloLectura)
    <div class="rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50 dark:bg-indigo-500/10 px-4 py-3 text-sm text-indigo-700 dark:text-indigo-400">
        Este es un rol primario: es de solo lectura y no puede editarse ni eliminarse.
    </div>
@endif

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
            Activa o desactiva cada permiso para este rol. Se precargan con la configuracion sugerida segun el rol padre.
        </p>
    </div>

    @foreach ($gruposPermisos as $grupo => $permisos)
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <p class="bg-slate-50 dark:bg-slate-800/60 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ \App\Domain\Organization\Models\Permission::grupoLabel($grupo) }}</p>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($permisos as $permiso)
                    @php
                        $activo = $this->permisoActivo($permiso);
                        $bloqueado = $this->permisoBloqueado($permiso);
                    @endphp
                    <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-700 dark:text-slate-200">{{ $permiso->nombre }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $permiso->slug }}</p>
                        </div>
                        <button type="button"
                                wire:click="togglePermiso({{ $permiso->id }}, '{{ $permiso->slug }}')"
                                @disabled($bloqueado)
                                role="switch" aria-checked="{{ $activo ? 'true' : 'false' }}" aria-label="{{ $permiso->nombre }}"
                                title="{{ $bloqueado && ! $soloLectura ? 'Este permiso solo se puede conceder desde un rol primario.' : '' }}"
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-60 disabled:cursor-not-allowed {{ $activo ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $activo ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
