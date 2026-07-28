import './bootstrap';
import Sortable from 'sortablejs';

window.toastNotifications = (initial = []) => ({
    toasts: [],
    init() {
        initial.forEach((toast) => this.add(toast));
    },
    add({ type = 'info', message = '' }) {
        if (!message) return;

        const toast = { id: Date.now() + Math.random(), type, message, visible: true };
        this.toasts.push(toast);
        window.setTimeout(() => this.remove(toast.id), type === 'error' ? 7000 : 4500);
    },
    remove(id) {
        const toast = this.toasts.find((item) => item.id === id);
        if (!toast) return;
        toast.visible = false;
        window.setTimeout(() => {
            this.toasts = this.toasts.filter((item) => item.id !== id);
        }, 200);
    },
});

// Las validaciones y addError() de cualquier componente Livewire llegan en
// el snapshot de respuesta. Se muestra una única notificación por mensaje.
document.addEventListener('livewire:init', () => {
    let previousErrors = new Set();

    Livewire.hook('commit', ({ succeed }) => {
        succeed(({ snapshot }) => {
            const parsedSnapshot = JSON.parse(snapshot);
            const currentErrors = new Set(Object.values(parsedSnapshot.memo.errors ?? {}).flat());

            currentErrors.forEach((message) => {
                if (previousErrors.has(message)) return;
                window.dispatchEvent(new CustomEvent('app-toast', {
                    detail: { type: 'error', message },
                }));
            });

            previousErrors = currentErrors;
        });
    });
});

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
 * PWA + Web Push.
 * Registra el service worker (sw.js) y, en pantallas autenticadas (el layout
 * expone la clave VAPID en <meta name="vapid-public-key">), suscribe este
 * navegador a notificaciones push y guarda la suscripcion en el servidor.
 * Si el permiso aun no fue decidido, se pide en la primera interaccion del
 * usuario (los navegadores exigen un gesto para mostrar el dialogo).
 */
const base64UrlAUint8Array = (base64Url) => {
    const relleno = '='.repeat((4 - (base64Url.length % 4)) % 4);
    const base64 = (base64Url + relleno).replace(/-/g, '+').replace(/_/g, '/');
    const crudo = atob(base64);
    return Uint8Array.from([...crudo].map((c) => c.charCodeAt(0)));
};

const vapidPublicKey = () => document.querySelector('meta[name="vapid-public-key"]')?.content?.trim() || '';

const registrarServiceWorker = async () => {
    const registro = await navigator.serviceWorker.register('/sw.js', {
        scope: '/',
        // En desktop, sin esto el navegador puede seguir con un sw.js viejo
        // cacheado y el push llega pero no muestra toast / no avisa a la pestaña.
        updateViaCache: 'none',
    });
    // En movil, subscribe() falla si el SW aun no esta activo.
    await navigator.serviceWorker.ready;
    // Forzar comprobacion de version nueva (projects-static-v4, postMessage, etc.).
    registro.update().catch(() => {});

    return registro;
};

/**
 * Cuando el push llega y la pestaña de escritorio esta abierta/enfocada,
 * Windows suele no mostrar el banner del sistema. El SW nos avisa y
 * mostramos el mismo mensaje como toast in-app.
 */
const escucharPushDesdeServiceWorker = () => {
    if (!('serviceWorker' in navigator) || escucharPushDesdeServiceWorker.listo) return;
    escucharPushDesdeServiceWorker.listo = true;

    navigator.serviceWorker.addEventListener('message', (event) => {
        const datos = event.data;
        if (!datos || datos.type !== 'push-received') return;

        const mensaje = [datos.title, datos.body].filter(Boolean).join(': ');
        if (!mensaje) return;

        window.dispatchEvent(new CustomEvent('app-toast', {
            detail: { type: 'info', message: mensaje },
        }));
    });
};

const guardarSuscripcionEnServidor = async (suscripcion) => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const respuesta = await fetch(`${window.location.origin}/push/subscribe`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(suscripcion.toJSON()),
    });

    if (!respuesta.ok) {
        const detalle = await respuesta.text().catch(() => '');
        throw new Error(`HTTP ${respuesta.status} al guardar suscripcion: ${detalle.slice(0, 200)}`);
    }
};

const suscribirPush = async (registro) => {
    const clave = vapidPublicKey();
    if (!clave || !('PushManager' in window) || Notification.permission !== 'granted') {
        return false;
    }

    try {
        let suscripcion = await registro.pushManager.getSubscription();
        const serverKey = base64UrlAUint8Array(clave);

        // Si ya hay suscripcion de otra clave VAPID, hay que recrearla.
        if (suscripcion) {
            try {
                await guardarSuscripcionEnServidor(suscripcion);
                return true;
            } catch (e) {
                console.warn('Web Push: suscripcion existente invalida, recreando…', e);
                await suscripcion.unsubscribe().catch(() => {});
                suscripcion = null;
            }
        }

        suscripcion = await registro.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: serverKey,
        });

        await guardarSuscripcionEnServidor(suscripcion);
        return true;
    } catch (e) {
        console.warn('Web Push: no se pudo suscribir', e);
        return false;
    }
};

let pushInicializado = false;
let pushEnCurso = null;

const configurarPush = async () => {
    if (!('serviceWorker' in navigator)) return;
    if (!vapidPublicKey()) return; // pantalla publica / sin VAPID
    if (pushEnCurso) return pushEnCurso;

    escucharPushDesdeServiceWorker();

    // Tras la primera suscripcion exitosa, revalidamos al volver a la pestana
    // (WNS/Edge en desktop caduca o se desincroniza con mas facilidad que FCM).
    if (pushInicializado) {
        if (Notification.permission !== 'granted') return;
        pushEnCurso = (async () => {
            try {
                const registro = await registrarServiceWorker();
                await suscribirPush(registro);
            } catch (e) {
                console.warn('Web Push: revalidacion fallida', e);
            } finally {
                pushEnCurso = null;
            }
        })();
        return pushEnCurso;
    }

    pushEnCurso = (async () => {
        try {
            const registro = await registrarServiceWorker();

            if (Notification.permission === 'granted') {
                const ok = await suscribirPush(registro);
                if (ok) pushInicializado = true;
            } else if (Notification.permission === 'default') {
                // Pedir permiso en el primer gesto del usuario
                const pedir = async () => {
                    if (await Notification.requestPermission() === 'granted') {
                        const ok = await suscribirPush(registro);
                        if (ok) pushInicializado = true;
                    }
                };
                document.addEventListener('pointerdown', pedir, { once: true });
                // No marcamos inicializado: si el usuario deniega, el boton
                // del sidebar puede reintentar via activarNotificaciones().
            } else {
                // denied: nada que hacer hasta que lo habilite en el navegador
                pushInicializado = true;
            }
        } catch (e) {
            console.warn('Service worker / push: configuracion fallida', e);
        } finally {
            pushEnCurso = null;
        }
    })();

    return pushEnCurso;
};

/**
 * Activacion manual desde el boton de la barra lateral. Devuelve el
 * permiso resultante ('granted' | 'denied' | 'default').
 */
window.activarNotificaciones = async () => {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) return 'denied';
    if (!vapidPublicKey()) return 'denied';
    // iOS solo permite push en PWA instalada (standalone).
    const ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const standalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    if (ios && !standalone) {
        console.warn('Web Push: en iOS hay que instalar la app en la pantalla de inicio.');
        return 'denied';
    }

    const permiso = await Notification.requestPermission();
    if (permiso === 'granted') {
        try {
            const registro = await registrarServiceWorker();
            const ok = await suscribirPush(registro);
            if (ok) pushInicializado = true;
        } catch (e) {
            console.warn('Web Push: activacion manual fallida', e);
        }
    }

    return permiso;
};

// Corre en la carga completa Y en cada navegacion SPA (wire:navigate):
// al entrar por el login (layout sin VAPID) el unico evento 'load' ya
// paso, asi que sin el segundo listener nunca se pediria el permiso.
window.addEventListener('load', configurarPush);
document.addEventListener('livewire:navigated', configurarPush);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        configurarPush();
    }
});

/**
 * Instalacion de la PWA con boton propio: Chrome/Edge (Android y desktop)
 * disparan beforeinstallprompt, que capturamos para mostrar "Instalar
 * aplicacion" en el sidebar y lanzar el dialogo nativo al pulsarlo.
 * En iOS no existe ese evento: el sidebar muestra las instrucciones
 * manuales (Compartir -> Anadir a pantalla de inicio).
 */
let promptInstalacion = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault(); // evita el mini-banner erratico; lo mostramos nosotros
    promptInstalacion = e;
    window.dispatchEvent(new CustomEvent('pwa-instalable'));
});

window.addEventListener('appinstalled', () => {
    promptInstalacion = null;
    window.dispatchEvent(new CustomEvent('pwa-instalada'));
});

window.pwaDisponible = () => promptInstalacion !== null;

window.instalarPWA = async () => {
    if (!promptInstalacion) return false;
    promptInstalacion.prompt();
    const eleccion = await promptInstalacion.userChoice;
    if (eleccion.outcome === 'accepted') {
        promptInstalacion = null;
    }
    return eleccion.outcome === 'accepted';
};

/**
 * Tema claro/oscuro: persiste la eleccion del usuario y expone
 * $store.theme.dark / .toggle() a cualquier x-data via Alpine (ya incluido
 * por Livewire, sin dependencias nuevas). El anti-FOUC en el <head> ya
 * aplico la clase inicial antes del primer pintado.
 */
const persistirTema = (dark) => {
    localStorage.setItem('theme', dark ? 'dark' : 'light');
    // El layout server-side decide la clase inicial de <html> desde esta
    // cookie (ver app.blade.php). Mantenerla en sync evita que, al navegar
    // con wire:navigate, el servidor pinte el tema equivocado por un instante
    // (pantallazo del fondo claro sobre las pantallas mas lentas, ej. dashboard).
    document.cookie = 'projects_theme=' + (dark ? 'dark' : 'light') + '; path=/; max-age=31536000; SameSite=Lax';
};

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            persistirTema(this.dark);
        },
    });

    // Alinea la cookie con el estado real al arrancar, por si el usuario
    // alterno el tema en una sesion previa sin recargar (cookie quedaria vieja).
    persistirTema(Alpine.store('theme').dark);

    Alpine.store('sidebar', {
        collapsed: localStorage.getItem('sidebar_collapsed') === '1',
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar_collapsed', this.collapsed ? '1' : '0');
            document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
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
