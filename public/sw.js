/**
 * Service worker de Projects (PWA).
 * - Cache ligero de assets estaticos (build de Vite e iconos).
 * - Recepcion de notificaciones Web Push y apertura de la app al tocarlas.
 *
 * Las paginas HTML autenticadas NUNCA se cachean (van con Cache-Control
 * no-store por seguridad de sesion), por eso el fetch handler solo actua
 * sobre assets estaticos.
 */
const CACHE = 'projects-static-v6';
const PRECACHE = ['/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    // Precachear iconos: si el push llega mientras PHP sigue ocupado enviando
    // a FCM/WNS (p. ej. artisan serve de un hilo), showNotification no depende
    // de una peticion de red al mismo origen.
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
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

    const titulo = datos.title || 'Projects';
    const cuerpo = datos.body || '';
    const ruta = datos.url || '/';
    const icono = new URL('/icons/icon-192.png', self.location.origin).href;
    const tag = datos.tag || ('projects-push-' + Date.now());

    event.waitUntil((async () => {
        const opciones = {
            body: cuerpo,
            icon: icono,
            badge: icono,
            data: { url: ruta },
            // En Windows/Edge, sin esto la toast desaparece al instante o
            // solo queda en el Centro de actividades.
            requireInteraction: true,
            renotify: true,
            tag,
        };

        try {
            await self.registration.showNotification(titulo, opciones);
        } catch {
            // Edge/WNS a veces rechaza requireInteraction; reintentar basico.
            await self.registration.showNotification(titulo, {
                body: cuerpo,
                icon: icono,
                badge: icono,
                data: { url: ruta },
                tag,
            });
        }
    })());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const ruta = (event.notification.data && event.notification.data.url) || '/';
    // Algunos navegadores (sobre todo moviles) exigen URL absoluta en openWindow.
    const destino = new URL(ruta, self.location.origin).href;

    event.waitUntil((async () => {
        const ventanas = await clients.matchAll({ type: 'window', includeUncontrolled: true });

        for (const ventana of ventanas) {
            let mismoOrigen = false;
            try {
                mismoOrigen = new URL(ventana.url).origin === self.location.origin;
            } catch {
                mismoOrigen = false;
            }
            if (!mismoOrigen) {
                continue;
            }

            await ventana.focus();

            // navigate() carga el destino (incl. ?tarea=) en esa pestana.
            if (typeof ventana.navigate === 'function') {
                try {
                    await ventana.navigate(destino);
                    return;
                } catch {
                    // Edge a veces falla; el cliente recarga via postMessage.
                }
            }

            ventana.postMessage({ type: 'notification-navigate', url: destino });
            return;
        }

        if (clients.openWindow) {
            await clients.openWindow(destino);
        }
    })());
});
