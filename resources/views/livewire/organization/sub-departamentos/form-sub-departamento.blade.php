<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-2xl mx-auto space-y-5 anim-fade-up">

        <x-page-header :title="$subDepartment ? 'Editar subdepartamento' : 'Nuevo subdepartamento'" subtitle="Division dentro de un departamento" icon="sitemap">
            <x-slot:actions>
                <a href="{{ route('subdepartamentos') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                    <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                </a>
            </x-slot:actions>
        </x-page-header>

        <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-6">

            <div>
                <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Departamento *</label>
                <select id="department_id" wire:model="department_id" @if ($subDepartment) disabled @endif
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm disabled:opacity-60">
                    <option value="">Selecciona un departamento</option>
                    @foreach ($departamentos as $d)
                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                    @endforeach
                </select>
                @if ($subDepartment)
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">El departamento no se puede cambiar una vez creado el subdepartamento.</p>
                @endif
                @error('department_id') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nombre *</label>
                <input id="nombre" type="text" wire:model="nombre" required
                       class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('nombre') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Descripción</label>
                <textarea id="descripcion" wire:model="descripcion" rows="3"
                          class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                @error('descripcion') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Icono</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Domain\Organization\Models\SubDepartment::ICONOS as $opcion)
                            <button type="button" wire:click="$set('icono', '{{ $opcion }}')"
                                    class="flex h-10 w-10 items-center justify-center rounded-lg border transition
                                           {{ $icono === $opcion ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400' : 'border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                <x-icon :name="$opcion" class="w-5 h-5" />
                            </button>
                        @endforeach
                    </div>
                    @error('icono') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Color</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Domain\Organization\Models\SubDepartment::COLORES as $clave => $conf)
                            <button type="button" wire:click="$set('color', '{{ $clave }}')" title="{{ $clave }}"
                                    class="h-8 w-8 rounded-full bg-gradient-to-br {{ $conf['gradiente'] }} transition
                                           {{ $color === $clave ? 'ring-2 ring-offset-2 ring-blue-500 dark:ring-offset-slate-900' : '' }}">
                            </button>
                        @endforeach
                    </div>
                    @error('color') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                <input type="checkbox" wire:model="activo" class="rounded border-gray-300 dark:border-slate-600 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
                Subdepartamento activo
            </label>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    {{ $subDepartment ? 'Guardar cambios' : 'Crear subdepartamento' }}
                </button>
                <a href="{{ route('subdepartamentos') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>
            </div>
        </form>
    </div>
</div>
