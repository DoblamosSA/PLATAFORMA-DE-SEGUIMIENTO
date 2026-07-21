@props(['title', 'subtitle' => null, 'icon' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        @if ($icon)
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-sky-600 text-white shadow-lg shadow-blue-500/30">
                <x-icon :name="$icon" class="w-6 h-6" />
            </span>
        @endif
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ $title }}</h1>
            @if ($subtitle)<p class="text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>@endif
        </div>
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
