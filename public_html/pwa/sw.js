const CACHE_VERSION = 'pwa-v1';
const CORE_CACHE = `${CACHE_VERSION}-core`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;

const BASE = (self.location && self.location.pathname ? self.location.pathname.replace(/\/pwa\/sw\.js$/, '') : '') || '';

const CORE_ASSETS = [
  `${BASE}/pwa/offline.html`,
  `${BASE}/pwa/pwa.css`,
  `${BASE}/pwa/pwa.js`,
  `${BASE}/pwa/manifest.webmanifest`,
  `${BASE}/assets/css/theme.css`,
  `${BASE}/assets/img/logobco.png`
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CORE_CACHE).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys
        .filter((k) => k.startsWith('pwa-') && k !== CORE_CACHE && k !== RUNTIME_CACHE)
        .map((k) => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

function isNavigationRequest(request) {
  return request.mode === 'navigate'
    || (request.headers.get('accept') || '').includes('text/html');
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  if (req.method !== 'GET') {
    return;
  }

  if (url.origin !== self.location.origin) {
    return;
  }

  const isPwaPath = url.pathname.startsWith(`${BASE}/pwa`);

  if (isPwaPath && isNavigationRequest(req)) {
    event.respondWith((async () => {
      try {
        const res = await fetch(req);
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(req, res.clone());
        return res;
      } catch (e) {
        const cached = await caches.match(req);
        if (cached) return cached;
        return caches.match(`${BASE}/pwa/offline.html`);
      }
    })());
    return;
  }

  if (url.pathname.startsWith(`${BASE}/assets/`) || url.pathname.startsWith(`${BASE}/pwa/`)) {
    event.respondWith((async () => {
      const cached = await caches.match(req);
      if (cached) return cached;
      const res = await fetch(req);
      const cache = await caches.open(RUNTIME_CACHE);
      cache.put(req, res.clone());
      return res;
    })());
    return;
  }
});

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {}

  const title = data.title || 'Notificacao';
  const options = {
    body: data.body || 'Voce tem uma atualizacao no aplicativo.',
    icon: `${BASE}/assets/img/logobco.png`,
    data: { url: (data.url || `${BASE}/pwa/dashboard`) }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification && event.notification.data && event.notification.data.url) || `${BASE}/pwa/dashboard`;
  event.waitUntil((async () => {
    const allClients = await self.clients.matchAll({ includeUncontrolled: true, type: 'window' });
    for (const client of allClients) {
      if (client.url.includes(url) && 'focus' in client) {
        return client.focus();
      }
    }
    if (self.clients.openWindow) {
      return self.clients.openWindow(url);
    }
  })());
});
