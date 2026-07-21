@props(['usuario' => null, 'nombre' => null, 'size' => 'h-9 w-9', 'text' => 'text-xs', 'tone' => 'brand'])

@php
    $nombreFinal = $usuario?->name ?? $nombre;
    $foto = $usuario?->fotoUrl();
    $iniciales = $usuario
        ? $usuario->iniciales()
        : ($nombreFinal ? collect(explode(' ', $nombreFinal))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') : '—');

    $tonos = [
        'brand' => 'bg-gradient-to-br from-blue-500 to-sky-600 text-white',
        'muted' => 'bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-600 dark:to-slate-700 text-slate-600 dark:text-slate-200',
    ];
@endphp

@if ($foto)
    <img src="{{ $foto }}" alt="Foto de {{ $nombreFinal }}" title="{{ $nombreFinal }}"
         {{ $attributes->merge(['class' => "$size shrink-0 rounded-full object-cover"]) }}>
@else
    <span title="{{ $nombreFinal }}"
          {{ $attributes->merge(['class' => "$size shrink-0 inline-flex items-center justify-center rounded-full $text font-semibold uppercase " . ($tonos[$tone] ?? $tonos['brand'])]) }}>
        {{ $iniciales }}
    </span>
@endif
