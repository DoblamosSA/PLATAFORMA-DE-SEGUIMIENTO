@props([
    'value' => 0,       // 0-100
    'label' => '',
    'size' => 160,
])

@php
    $v = max(0, min(100, (float) $value));
    $r = 54;
    $circ = 2 * M_PI * $r;
    $offset = $circ * (1 - $v / 100);
    // Color segun nivel de cumplimiento
    $color = $v >= 90 ? '#10b981' : ($v >= 70 ? '#f59e0b' : '#f43f5e');
    $id = 'g' . substr(md5($label . $value), 0, 6);
@endphp

<div class="flex flex-col items-center justify-center">
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
            <defs>
                <linearGradient id="{{ $id }}" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.9"/>
                    <stop offset="100%" stop-color="{{ $color }}"/>
                </linearGradient>
            </defs>
            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="var(--surface-gauge-track)" stroke-width="11"/>
            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="url(#{{ $id }})" stroke-width="11"
                    stroke-linecap="round"
                    stroke-dasharray="{{ $circ }}"
                    stroke-dashoffset="{{ $offset }}"
                    style="transition: stroke-dashoffset 1s cubic-bezier(.22,1,.36,1);"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-3xl font-extrabold text-slate-800 dark:text-slate-100">{{ rtrim(rtrim(number_format($v, 1), '0'), '.') }}%</span>
            @if ($label)<span class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $label }}</span>@endif
        </div>
    </div>
</div>
