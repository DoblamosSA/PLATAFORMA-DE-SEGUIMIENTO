@props([
    'evidencias' => [],
    'puedeEliminar' => false,
    'eliminarMetodo' => 'eliminarEvidencia',
])

@php
    $items = collect($evidencias)->values()->map(fn ($e) => [
        'id' => $e->id,
        'url' => $e->url(),
        'nombre' => $e->nombre_original,
    ])->all();
@endphp

@if (count($items) > 0)
    <div class="mt-2"
         x-data="{
             abierto: false,
             indice: 0,
             items: @js($items),
             abrir(i) { this.indice = i; this.abierto = true; },
             cerrar() { this.abierto = false; },
             prev() { this.indice = (this.indice - 1 + this.items.length) % this.items.length; },
             next() { this.indice = (this.indice + 1) % this.items.length; },
         }"
         @keydown.escape.window="if (abierto) cerrar()"
         @keydown.left.window="if (abierto) prev()"
         @keydown.right.window="if (abierto) next()">
        <div class="flex flex-wrap gap-2">
            <template x-for="(item, i) in items" :key="item.id">
                <div class="relative group">
                    <button type="button" @click="abrir(i)"
                            class="block h-16 w-16 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <img :src="item.url" :alt="item.nombre" class="h-full w-full object-cover" loading="lazy">
                    </button>
                    @if ($puedeEliminar)
                        <button type="button"
                                x-on:click.stop="$dispatch('confirm-modal', {
                                    title: 'Eliminar evidencia',
                                    message: '¿Eliminar esta imagen de evidencia?',
                                    confirmText: 'Eliminar',
                                    danger: true,
                                    onConfirm: () => $wire.{{ $eliminarMetodo }}(item.id),
                                })"
                                class="absolute -right-1.5 -top-1.5 hidden group-hover:inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-white shadow"
                                aria-label="Eliminar evidencia">
                            <x-icon name="close" class="w-3 h-3" />
                        </button>
                    @endif
                </div>
            </template>
        </div>

        {{-- Visor lightbox --}}
        <div x-show="abierto" x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/80 p-4"
             @click.self="cerrar()"
             role="dialog" aria-modal="true" aria-label="Visor de evidencias">
            <button type="button" @click="cerrar()"
                    class="absolute right-4 top-4 rounded-lg bg-white/10 p-2 text-white hover:bg-white/20"
                    aria-label="Cerrar">
                <x-icon name="close" class="w-5 h-5" />
            </button>
            <button type="button" x-show="items.length > 1" @click="prev()"
                    class="absolute left-3 sm:left-6 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
                    aria-label="Anterior">
                <x-icon name="chevron-left" class="w-6 h-6" />
            </button>
            <img :src="items[indice]?.url" :alt="items[indice]?.nombre"
                 class="max-h-[85vh] max-w-[90vw] rounded-lg object-contain shadow-2xl"
                 @click.stop>
            <button type="button" x-show="items.length > 1" @click="next()"
                    class="absolute right-3 sm:right-6 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
                    aria-label="Siguiente">
                <x-icon name="chevron-right" class="w-6 h-6" />
            </button>
            <p class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black/50 px-3 py-1 text-xs text-white"
               x-text="(indice + 1) + ' / ' + items.length"></p>
        </div>
    </div>
@endif
