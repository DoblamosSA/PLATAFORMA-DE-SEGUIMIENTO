<div class="p-4 sm:p-6 lg:p-8 space-y-6 anim-stagger">

    <x-page-header title="Permisos" subtitle="Catálogo de permisos del sistema (solo lectura)" icon="key" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($grupos as $grupo => $permisos)
            <x-card :title="ucfirst($grupo ?? 'general')">
                <ul class="space-y-2">
                    @foreach ($permisos as $permiso)
                        <li class="flex items-start gap-2">
                            <x-icon name="key" class="w-4 h-4 mt-0.5 text-slate-400 dark:text-slate-500 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $permiso->nombre }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 font-mono truncate">{{ $permiso->slug }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <x-empty-state icon="key" mensaje="No hay permisos sembrados todavía." />
            </div>
        @endforelse
    </div>
</div>
