@props([
    'label' => '',
    'value' => '',
    'icon' => 'sparkles',
    'tone' => 'indigo',    // indigo | emerald | amber | rose | sky | slate
    'hint' => null,
])

@php
    $tones = [
        'indigo'  => ['from-indigo-500 to-violet-600', 'text-indigo-600', 'shadow-indigo-500/30'],
        'emerald' => ['from-emerald-500 to-teal-600', 'text-emerald-600', 'shadow-emerald-500/30'],
        'amber'   => ['from-amber-500 to-orange-600', 'text-amber-600', 'shadow-amber-500/30'],
        'rose'    => ['from-rose-500 to-pink-600', 'text-rose-600', 'shadow-rose-500/30'],
        'sky'     => ['from-sky-500 to-blue-600', 'text-sky-600', 'shadow-sky-500/30'],
        'slate'   => ['from-slate-500 to-slate-700', 'text-slate-600', 'shadow-slate-500/30'],
    ];
    [$grad, $txt, $shadow] = $tones[$tone] ?? $tones['indigo'];
@endphp

<div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-200/70 p-5 shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $grad }} opacity-[0.07] transition group-hover:opacity-[0.12]"></div>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold {{ $txt }}">{{ $value }}</p>
        </div>
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-lg {{ $shadow }}">
            <x-icon :name="$icon" class="w-5 h-5" />
        </span>
    </div>
    @if ($hint)<p class="mt-3 text-xs text-slate-400">{{ $hint }}</p>@endif
</div>
