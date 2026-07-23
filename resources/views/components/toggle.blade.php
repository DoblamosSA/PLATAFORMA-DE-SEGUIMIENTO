@props(['label' => null])

<label class="inline-flex items-center gap-3 text-sm text-gray-700 dark:text-slate-300 cursor-pointer select-none">
    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
        <input type="checkbox" {{ $attributes->merge(['class' => 'peer sr-only']) }}>
        <span class="pointer-events-none absolute inset-0 rounded-full bg-slate-300 dark:bg-slate-700 transition-colors peer-checked:bg-emerald-500 peer-disabled:opacity-60"></span>
        <span class="pointer-events-none inline-block h-4 w-4 translate-x-1 rounded-full bg-white shadow transform transition-transform peer-checked:translate-x-6"></span>
    </span>
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>
