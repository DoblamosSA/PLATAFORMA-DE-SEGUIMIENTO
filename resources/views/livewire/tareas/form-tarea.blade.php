@if ($enModal)
    <form wire:submit="save" class="space-y-5">
        @include('livewire.tareas.partials.campos-formulario')

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                {{ $task ? 'Guardar cambios' : 'Crear tarea' }}
            </button>
            <button type="button" wire:click="cancelar" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</button>

            @if ($puedeEliminar)
                <button type="button"
                        x-on:click="$dispatch('confirm-modal', {
                            title: 'Eliminar',
                            message: '¿Eliminar esta tarea? Esta acción no se puede deshacer.',
                            confirmText: 'Eliminar',
                            danger: true,
                            onConfirm: () => $wire.eliminar(),
                        })"
                        class="ml-auto text-sm font-medium text-rose-600 dark:text-rose-400 hover:underline">
                    Eliminar tarea
                </button>
            @elseif ($task && $task->subtareas->isNotEmpty())
                <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">No se puede eliminar: tiene subtareas.</span>
            @endif
        </div>
    </form>

    <div class="mt-5">
        @include('livewire.tareas.partials.bitacora')
    </div>
@else
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-4xl mx-auto space-y-5 anim-fade-up">

            <x-page-header :title="$task ? 'Editar tarea' : 'Nueva tarea'" subtitle="Define la actividad y su asignacion" icon="tasks">
                <x-slot:actions>
                    <a href="{{ route('tareas') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                        <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                    </a>
                </x-slot:actions>
            </x-page-header>

            <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-5">
                @include('livewire.tareas.partials.campos-formulario')

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                        {{ $task ? 'Guardar cambios' : 'Crear tarea' }}
                    </button>
                    <a href="{{ route('tareas') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>

                    @if ($puedeEliminar)
                        <button type="button"
                                x-on:click="$dispatch('confirm-modal', {
                                    title: 'Eliminar',
                                    message: '¿Eliminar esta tarea? Esta acción no se puede deshacer.',
                                    confirmText: 'Eliminar',
                                    danger: true,
                                    onConfirm: () => $wire.eliminar(),
                                })"
                                class="ml-auto text-sm font-medium text-rose-600 dark:text-rose-400 hover:underline">
                            Eliminar tarea
                        </button>
                    @elseif ($task && $task->subtareas->isNotEmpty())
                        <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">No se puede eliminar: tiene subtareas.</span>
                    @endif
                </div>
            </form>

            @include('livewire.tareas.partials.bitacora')
        </div>
    </div>
@endif
