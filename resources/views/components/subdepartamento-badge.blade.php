@props(['subdepartamento'])

@if ($subdepartamento)
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $subdepartamento->colores()['badge'] }}">
        <x-icon :name="$subdepartamento->icono" class="w-3 h-3" />
        {{ $subdepartamento->nombre }}
    </span>
@else
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-slate-500/15 dark:text-slate-400">—</span>
@endif
