@props(['variant' => 'ver', 'href' => null, 'label' => null, 'icon' => null, 'confirm' => null, 'confirmText' => null])

@php
    $variantes = [
        'editar'   => ['icon' => 'pencil', 'color' => 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 focus-visible:ring-blue-500'],
        'ver'      => ['icon' => 'eye', 'color' => 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 focus-visible:ring-slate-400'],
        'duplicar' => ['icon' => 'copy', 'color' => 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 focus-visible:ring-indigo-500'],
        'eliminar' => ['icon' => 'trash', 'color' => 'text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 focus-visible:ring-rose-500'],
    ];
    $conf = $variantes[$variant] ?? $variantes['ver'];
    if ($icon) {
        $conf['icon'] = $icon;
    }
    $clases = 'inline-flex items-center justify-center w-8 h-8 rounded-lg transition disabled:opacity-40 disabled:pointer-events-none focus-visible:outline-none focus-visible:ring-2 '.$conf['color'];
    $accionWire = $attributes->get('wire:click');
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate
       {{ $attributes->merge(['class' => $clases, 'title' => $label, 'aria-label' => $label]) }}>
        <x-icon :name="$conf['icon']" class="w-4 h-4" />
    </a>
@elseif ($confirm && $accionWire)
    <button type="button"
            x-on:click="$dispatch('confirm-modal', {
                title: @js($variant === 'eliminar' ? 'Eliminar' : 'Confirmar acción'),
                message: @js($confirm),
                confirmText: @js($confirmText ?? ($variant === 'eliminar' ? 'Eliminar' : 'Confirmar')),
                danger: {{ $variant === 'eliminar' ? 'true' : 'false' }},
                onConfirm: () => { $wire.{{ $accionWire }} },
            })"
            {{ $attributes->except(['wire:click', 'wire:confirm'])->merge(['class' => $clases, 'title' => $label, 'aria-label' => $label]) }}>
        <x-icon :name="$conf['icon']" class="w-4 h-4" />
    </button>
@else
    <button type="button"
            {{ $attributes->merge(['class' => $clases, 'title' => $label, 'aria-label' => $label]) }}>
        <x-icon :name="$conf['icon']" class="w-4 h-4" />
    </button>
@endif
