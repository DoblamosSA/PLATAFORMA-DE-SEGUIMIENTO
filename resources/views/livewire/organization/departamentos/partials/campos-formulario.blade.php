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

<div>
    <label for="responsable_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Responsable *</label>
    <select id="responsable_id" wire:model="responsable_id"
            class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">Selecciona un administrador</option>
        @foreach ($administradores as $admin)
            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
        @endforeach
    </select>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">El responsable de un departamento siempre debe ser un Administrador.</p>
    @error('responsable_id') <span class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
</div>

<x-toggle wire:model="activo" label="Departamento activo" />
