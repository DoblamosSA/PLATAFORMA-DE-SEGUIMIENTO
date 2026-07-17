@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white/90 backdrop-blur border border-slate-200/70 shadow-sm shadow-slate-200/50']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 px-5 pt-5 pb-3">
            <div>
                @if ($title)<h2 class="text-sm font-semibold text-slate-700">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="text-xs text-slate-400 mt-0.5">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions){{ $actions }}@endisset
        </div>
    @endif
    <div class="{{ $title ? 'px-5 pb-5' : 'p-5' }}">
        {{ $slot }}
    </div>
</div>
