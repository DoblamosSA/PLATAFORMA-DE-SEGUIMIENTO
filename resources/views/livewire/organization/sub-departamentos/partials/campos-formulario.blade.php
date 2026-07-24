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

{{-- Icono y color se manejan en Alpine (cliente) y se sincronizan con
     Livewire via @entangle: la seleccion responde al instante, sin ir y
     volver al servidor en cada clic (ese round-trip es lo que reiniciaba
     el resto de campos del formulario cuando se monta dentro del modal). --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"
     x-data="{ icono: @entangle('icono'), color: @entangle('color') }">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Icono</label>
        <div class="flex flex-wrap gap-2">
            @foreach (\App\Domain\Organization\Models\SubDepartment::ICONOS as $opcion)
                <button type="button" @click="icono = '{{ $opcion }}'"
                        :class="icono === '{{ $opcion }}'
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400'
                            : 'border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
                        class="flex h-10 w-10 items-center justify-center rounded-lg border transition">
                    <x-icon :name="$opcion" class="w-5 h-5" />
                </button>
            @endforeach
        </div>
        @error('icono') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Color</label>
        <div class="grid grid-cols-6 sm:grid-cols-8 gap-2">
            @foreach (\App\Domain\Organization\Models\SubDepartment::COLORES as $clave => $conf)
                <button type="button" @click="color = '{{ $clave }}'" title="{{ ucfirst($clave) }}"
                        :class="color === '{{ $clave }}' ? 'ring-2 ring-offset-2 ring-blue-500 dark:ring-offset-slate-900' : ''"
                        class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br {{ $conf['gradiente'] }} shadow-sm transition hover:scale-105">
                    <x-icon x-show="color === '{{ $clave }}'" name="check" class="w-4 h-4 text-white drop-shadow" />
                </button>
            @endforeach
        </div>
        @error('color') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
    </div>
</div>

<x-toggle wire:model="activo" label="Subdepartamento activo" />
