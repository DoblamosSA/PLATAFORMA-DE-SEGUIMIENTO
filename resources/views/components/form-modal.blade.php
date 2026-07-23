{{--
    Modal reutilizable para formularios de crear/editar. A diferencia de
    x-confirm-modal (estado 100% cliente/Alpine), este modal refleja el
    estado real del componente Livewire padre (show), porque el contenido
    (el formulario) depende de datos del servidor (que registro se edita).
--}}
@props(['show' => false, 'title' => '', 'wireClose' => 'cerrarModal', 'maxWidth' => 'lg'])

@php
    $anchoClase = [
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
    ][$maxWidth] ?? 'max-w-lg';
@endphp

@if ($show)
    <div class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto"
         x-data x-on:keydown.escape.window="$wire.{{ $wireClose }}()" wire:transition>
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm" wire:click="{{ $wireClose }}"></div>

        <div class="relative w-full {{ $anchoClase }} my-8 rounded-2xl bg-white dark:bg-slate-900 shadow-2xl dark:shadow-black/50 border border-slate-200/70 dark:border-slate-800">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h2>
                <button type="button" wire:click="{{ $wireClose }}"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 transition">
                    <x-icon name="close" class="w-5 h-5" />
                </button>
            </div>
            <div class="p-6 max-h-[75vh] overflow-y-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
@endif
