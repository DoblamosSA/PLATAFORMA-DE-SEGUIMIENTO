@if ($task && $bitacora->isNotEmpty())
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">Historial</h2>
        <ul class="space-y-2">
            @foreach ($bitacora as $act)
                <li class="flex items-start gap-3 text-sm">
                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-400 shrink-0"></span>
                    <div>
                        <span class="text-gray-700 dark:text-slate-300">{{ $act->detalle }}</span>
                        <span class="block text-xs text-gray-400 dark:text-slate-500">
                            {{ $act->user?->name ?? 'Sistema' }} · {{ $act->created_at->diffForHumans() }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
