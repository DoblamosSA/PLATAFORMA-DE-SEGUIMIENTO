import './bootstrap';
import Sortable from 'sortablejs';

/**
 * Guardia de sesion contra el back/forward cache (bfcache): al pulsar
 * "atras" el navegador puede restaurar una pantalla completa desde memoria
 * sin consultar al servidor (mostrando p. ej. el dashboard tras cerrar
 * sesion). Si la pagina viene del bfcache, se fuerza una peticion real:
 * el servidor redirige al login cuando ya no hay sesion.
 */
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});

/**
 * Tema claro/oscuro: persiste la eleccion del usuario y expone
 * $store.theme.dark / .toggle() a cualquier x-data via Alpine (ya incluido
 * por Livewire, sin dependencias nuevas). El anti-FOUC en el <head> ya
 * aplico la clase inicial antes del primer pintado.
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    });
});

/**
 * En cada wire:navigate, Livewire sincroniza los atributos de <html> con
 * los del documento recien cargado (ver replaceHtmlAttributes en su
 * cliente) — y el HTML que renderiza el servidor nunca incluye la clase
 * "dark" (esa la agrega solo el script anti-FOUC del navegador). Sin este
 * listener, cada navegacion borraria el tema aunque localStorage y el
 * store sigan correctos, hasta la proxima recarga completa.
 */
document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.toggle('dark', window.Alpine?.store('theme')?.dark ?? false);
});

/**
 * Reinicia las animaciones de apertura (page-enter y anim-*) en cada
 * navegacion SPA (wire:navigate): si Livewire conserva los elementos y
 * solo actualiza su contenido, la animacion CSS no volveria a correr sin
 * quitar y re-agregar la clase con un reflow de por medio.
 */
document.addEventListener('livewire:navigated', () => {
    const selector = '#main-content, .anim-fade-in, .anim-fade-up, .anim-fade-right, .anim-stagger, .anim-stagger-x';
    document.querySelectorAll(selector).forEach((el) => {
        const clases = [...el.classList].filter((c) => c === 'page-enter' || c.startsWith('anim-'));
        if (!clases.length) return;
        el.classList.remove(...clases);
        void el.offsetWidth; // fuerza reflow para reiniciar la animacion CSS
        el.classList.add(...clases);
    });
});

/**
 * Inicializa el arrastre de cards en una columna del tablero Kanban.
 * Al soltar una card notifica al componente Livewire (moverTarea) con la
 * columna destino y el orden resultante de esa columna.
 *
 * Uso en Blade:
 *   <div x-init="window.kanbanSortable($el, $wire)" data-column-id="{{ $col->id }}"> ... </div>
 */
window.kanbanSortable = (el, wire) => {
    return Sortable.create(el, {
        group: 'kanban-cards',
        animation: 150,
        draggable: '[data-card]',
        handle: '[data-card]',
        ghostClass: 'kanban-ghost',
        dragClass: 'kanban-drag',
        onEnd: (evt) => {
            const destino = evt.to;
            const columnId = parseInt(destino.dataset.columnId, 10);
            const taskId = parseInt(evt.item.dataset.taskId, 10);
            const orden = [...destino.querySelectorAll('[data-card]')]
                .map((n) => parseInt(n.dataset.taskId, 10));

            wire.moverTarea(taskId, columnId, orden);
        },
    });
};

/**
 * Permite reordenar las columnas del tablero arrastrando por su encabezado.
 *
 * Uso en Blade:
 *   <div x-init="window.kanbanColumns($el, $wire)"> ...columnas... </div>
 */
window.kanbanColumns = (el, wire) => {
    return Sortable.create(el, {
        group: 'kanban-columns',
        animation: 150,
        draggable: '[data-column]',
        handle: '[data-column-handle]',
        ghostClass: 'kanban-ghost',
        onEnd: (evt) => {
            const orden = [...el.querySelectorAll('[data-column]')]
                .map((n) => parseInt(n.dataset.columnId, 10));

            wire.reordenarColumnas(orden);
        },
    });
};
