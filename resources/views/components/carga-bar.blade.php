@props(['porcentaje' => 0, 'estado' => 'disponible'])

@php
    $colores = [
        'disponible' => 'bg-emerald-500',
        'alta'       => 'bg-amber-500',
        'al_limite'  => 'bg-rose-500',
    ];
    $textos = [
        'disponible' => 'text-emerald-600 dark:text-emerald-400',
        'alta'       => 'text-amber-600 dark:text-amber-400',
        'al_limite'  => 'text-rose-600 dark:text-rose-400',
    ];
    $anchoVisual = min((float) $porcentaje, 100);
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}
     role="progressbar" aria-valuenow="{{ (int) round($porcentaje) }}" aria-valuemin="0" aria-valuemax="100"
     aria-label="Carga de trabajo: {{ (int) round($porcentaje) }} por ciento">
    <div class="flex items-center justify-between text-[11px] mb-1">
        <span class="font-medium {{ $textos[$estado] ?? $textos['disponible'] }}">{{ (int) round($porcentaje) }}%</span>
    </div>
    <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
        <div class="h-full rounded-full {{ $colores[$estado] ?? $colores['disponible'] }} transition-all duration-500" style="width: {{ $anchoVisual }}%"></div>
    </div>
</div>
