@props(['icon' => 'folder', 'mensaje' => 'No hay elementos todavía.'])

<div class="flex flex-col items-center justify-center gap-2 py-10 text-center text-slate-400 dark:text-slate-500">
    <x-icon :name="$icon" class="w-8 h-8 text-slate-300 dark:text-slate-600" />
    <p>{{ $mensaje }}</p>
    @if ($slot->isNotEmpty())
        <p>{{ $slot }}</p>
    @endif
</div>
