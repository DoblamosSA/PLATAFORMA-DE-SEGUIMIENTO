@props([
    'propiedad' => 'evidenciasPendientes',
    'alTerminar' => null,
    'etiqueta' => 'Adjuntar evidencia',
])

{{--
    Selector de imagenes opcionales. Comprime en el cliente y sube a Livewire
    con uploadMultiple (propiedad array). Si alTerminar es un metodo Livewire,
    se invoca al completar la subida (p. ej. persistir de inmediato).
--}}
<label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-dashed border-slate-300 dark:border-slate-600 bg-white/60 dark:bg-slate-800/60 px-2.5 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-300 transition">
    <x-icon name="photo" class="w-3.5 h-3.5" />
    <span>{{ $etiqueta }}</span>
    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp,image/jpg" multiple
           x-on:change="await window.subirEvidenciasComprimidas($event, $wire, @js($propiedad), {
               alTerminar: {{ $alTerminar ? "() => \$wire.{$alTerminar}()" : 'null' }}
           })">
</label>
@error($propiedad) <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</span> @enderror
@error($propiedad.'.*') <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</span> @enderror
