/* SnrFit — Service Worker
 * Estratégias:
 *  - Navegações (HTML): network-first com fallback para cache e, por fim, offline.html.
 *  - Assets estáticos same-origin (css/js/img/fontes): stale-while-revalidate.
 *  - Demais requisições e cross-origin: passam direto (sem cache).
 * Apenas requisições GET são tratadas.
 */
const VERSION = 'snrfit-v1';
const STATIC_CACHE = `${VERSION}-static`;
const RUNTIME_CACHE = `${VERSION}-runtime`;

const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return /\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|svg|ico|woff2?|ttf|eot)$/i.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
    const req = event.request;

    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return; // cross-origin: deixa o browser cuidar

    // Navegações de página: network-first
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(RUNTIME_CACHE).then((c) => c.put(req, copy)).catch(() => {});
                    return res;
                })
                .catch(() => caches.match(req).then((cached) => cached || caches.match('/offline.html')))
        );
        return;
    }

    // Assets estáticos: stale-while-revalidate
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(req).then((cached) => {
                const network = fetch(req).then((res) => {
                    if (res && res.status === 200) {
                        const copy = res.clone();
                        caches.open(RUNTIME_CACHE).then((c) => c.put(req, copy)).catch(() => {});
                    }
                    return res;
                }).catch(() => cached);
                return cached || network;
            })
        );
    }
});

// Permite atualização imediata quando uma nova versão do SW for instalada.
self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') self.skipWaiting();
});
