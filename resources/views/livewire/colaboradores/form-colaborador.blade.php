<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-3xl mx-auto space-y-5">

        <x-page-header :title="$colaborador ? 'Editar colaborador' : 'Nuevo colaborador'" subtitle="Datos, rol y disponibilidad" icon="users">
            <x-slot:actions>
                <a href="{{ route('colaboradores') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                    <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                </a>
            </x-slot:actions>
        </x-page-header>

        <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-6">

            {{-- Foto --}}
            <div class="flex items-center gap-4">
                @if ($foto)
                    <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa de la foto" class="h-16 w-16 rounded-full object-cover shrink-0">
                @else
                    <x-avatar :usuario="$colaborador" :nombre="$colaborador ? null : '?'" size="h-16 w-16" text="text-lg" />
                @endif
                <div class="flex-1">
                    <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Foto</label>
                    <input id="foto" type="file" wire:model="foto" accept="image/*"
                           class="w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 dark:file:bg-blue-500/15 file:px-3 file:py-1.5 file:text-blue-700 dark:file:text-blue-400 file:text-sm">
                    <div wire:loading wire:target="foto" class="text-xs text-slate-400 dark:text-slate-500 mt-1">Subiendo…</div>
                    @error('foto') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nombre *</label>
                    <input id="name" type="text" wire:model="name" required
                           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Correo *</label>
                    <input id="email" type="email" wire:model="email" required
                           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('email') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Teléfono</label>
                    <input id="telefono" type="text" wire:model="telefono"
                           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('telefono') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="rol" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Rol *</label>
                    <select id="rol" wire:model="rol" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        @foreach (\App\Models\User::ROLES_LABEL as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('rol') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="area" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Área *</label>
                    <select id="area" wire:model="area" class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm">
                        <option value="software">Software</option>
                        <option value="soporte">Soporte</option>
                        <option value="infraestructura">Infraestructura</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div>
                    <label for="cargo" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Cargo</label>
                    <input id="cargo" type="text" wire:model="cargo"
                           class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                <input type="checkbox" wire:model="activo" class="rounded border-gray-300 dark:border-slate-600 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                Colaborador activo
            </label>

            {{-- Contraseña: solo se captura aqui, nunca se muestra --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-4 space-y-3">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Contraseña {{ $colaborador ? '(dejar en blanco para no cambiarla)' : '*' }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nueva contraseña</label>
                        <input id="password" type="password" wire:model="password" autocomplete="new-password"
                               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('password') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Confirmar contraseña</label>
                        <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Disponibilidad --}}
            <div class="rounded-xl border border-blue-100 dark:border-blue-500/30 bg-blue-50/40 dark:bg-blue-500/10 p-4 space-y-3">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Disponibilidad</p>

                <div>
                    <span class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-2">Días laborales *</span>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Días laborales">
                        @foreach (\App\Models\User::DIAS as $dia)
                            <label class="inline-flex items-center justify-center h-9 w-9 rounded-lg border text-sm font-semibold cursor-pointer transition
                                          {{ in_array($dia, $diasLaborales) ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300' }}
                                          focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-1">
                                <input type="checkbox" wire:model.live="diasLaborales" value="{{ $dia }}" class="sr-only" aria-label="Día {{ $dia }}">
                                {{ $dia }}
                            </label>
                        @endforeach
                    </div>
                    @error('diasLaborales') <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <label for="horasDiarias" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Horas diarias * (máx. 12)</label>
                        <input id="horasDiarias" type="number" step="0.5" min="0.5" max="12" wire:model.live="horasDiarias"
                               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('horasDiarias') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Capacidad semanal</p>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ rtrim(rtrim(number_format($capacidadSemanal, 2), '0'), '.') }} h</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    {{ $colaborador ? 'Guardar cambios' : 'Crear colaborador' }}
                </button>
                <a href="{{ route('colaboradores') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>
            </div>
        </form>
    </div>
</div>
