{{--
    Modal de confirmacion reutilizable, montado una sola vez en el layout.
    Cualquier parte de la app lo dispara asi:

        x-on:click="$dispatch('confirm-modal', {
            title: 'Eliminar rol',
            message: '¿Eliminar el rol \"Coordinador\"? Esta accion no se puede deshacer.',
            confirmText: 'Eliminar',
            danger: true,
            onConfirm: () => $wire.eliminar(5),
        })"

    onConfirm puede ser cualquier funcion JS, tipicamente una llamada a $wire
    (magic property de Livewire, disponible en cualquier x-on dentro del componente).
--}}
<div x-data="{
        open: false,
        title: 'Confirmar acción',
        message: '¿Deseas continuar?',
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        danger: true,
        onConfirm: null,
    }"
    x-on:confirm-modal.window="
        title = $event.detail.title ?? 'Confirmar acción';
        message = $event.detail.message ?? '¿Deseas continuar?';
        confirmText = $event.detail.confirmText ?? 'Confirmar';
        cancelText = $event.detail.cancelText ?? 'Cancelar';
        danger = $event.detail.danger ?? true;
        onConfirm = $event.detail.onConfirm ?? null;
        open = true;
    "
    x-on:keydown.escape.window="open = false"
    x-show="open"
    style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    role="alertdialog"
    aria-modal="true"
    :aria-label="title">

    <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"
         x-on:click="open = false"
         x-show="open" x-transition.opacity></div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 shadow-2xl dark:shadow-black/50 border border-slate-200/70 dark:border-slate-800 p-6">
        <div class="flex items-start gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                  :class="danger ? 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400'">
                <x-icon name="alert" class="w-5 h-5" x-show="danger" style="display:none;"></x-icon>
                <x-icon name="shield-check" class="w-5 h-5" x-show="!danger" style="display:none;"></x-icon>
            </span>
            <div class="min-w-0 pt-1.5">
                <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100" x-text="title"></h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="message"></p>
            </div>
        </div>
        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" x-on:click="open = false"
                    class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-[0.98] transition"
                    x-text="cancelText"></button>
            <button type="button"
                    x-on:click="open = false; if (onConfirm) onConfirm()"
                    class="rounded-xl px-4 py-2 text-sm font-medium text-white shadow-lg active:scale-[0.98] transition"
                    :class="danger ? 'bg-gradient-to-br from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 shadow-rose-500/30' : 'bg-gradient-to-br from-blue-600 to-sky-600 hover:from-blue-700 hover:to-sky-600 shadow-blue-500/30'"
                    x-text="confirmText"></button>
        </div>
    </div>
</div>
