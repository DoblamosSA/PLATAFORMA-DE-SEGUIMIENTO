/**
 * Service worker de Projects (PWA).
 * - Cache ligero de assets estaticos (build de Vite e iconos).
 * - Recepcion de notificaciones Web Push y apertura de la app al tocarlas.
 *
 * Las paginas HTML autenticadas NUNCA se cachean (van con Cache-Control
 * no-store por seguridad de sesion), por eso el fetch handler solo actua
 * sobre assets estaticos.
 */
const CACHE = 'projects-static-v2';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    const esAssetEstatico = url.origin === self.location.origin
        && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/'));

    if (event.request.method !== 'GET' || !esAssetEstatico) {
        return; // el navegador lo maneja normal (paginas, Livewire, dev server)
    }

    event.respondWith(
        caches.match(event.request).then((cacheado) => {
            if (cacheado) return cacheado;

            return fetch(event.request).then((respuesta) => {
                if (respuesta.ok) {
                    const copia = respuesta.clone();
                    caches.open(CACHE).then((cache) => cache.put(event.request, copia));
                }
                return respuesta;
            });
        })
    );
});

self.addEventListener('push', (event) => {
    let datos = {};
    try {
        datos = event.data ? event.data.json() : {};
    } catch {
        datos = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(
        self.registration.showNotification(datos.title || 'Projects', {
            body: datos.body || '',
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            data: { url: datos.url || '/' },
            // Ayuda en Android a agrupar y no silenciar notificaciones.
            renotify: true,
            tag: datos.tag || 'projects-push',
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const ruta = (event.notification.data && event.notification.data.url) || '/';
    // Algunos navegadores (sobre todo moviles) exigen URL absoluta en openWindow.
    const destino = new URL(ruta, self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((ventanas) => {
            for (const ventana of ventanas) {
                if ('focus' in ventana) {
                    ventana.focus();
                    if ('navigate' in ventana) {
                        return ventana.navigate(destino);
                    }
                    return;
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(destino);
            }
        })
    );
});
