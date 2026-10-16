// SIMBAKO App Shell Service Worker
const CACHE_NAME = 'simbako-shell-v1';
const SHELL_ASSETS = [
  '/',
  '/manifest.json',
  '/build/assets/fonts-C9MNnjVw.css',
  '/build/assets/app-G-jcyPjs.css',
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(SHELL_ASSETS).catch(() => {
        // Cache failures during initial install are safely ignored
      });
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const requestUrl = new URL(event.request.url);

  // NEVER cache API or state-mutating requests
  if (
    requestUrl.pathname.startsWith('/api') ||
    requestUrl.pathname.startsWith('/jual/') ||
    event.request.method !== 'GET'
  ) {
    return;
  }

  // Network-first for HTML pages to ensure fresh data, fallback to cache
  if (event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  // Cache-first for static build assets
  if (requestUrl.pathname.startsWith('/build/')) {
    event.respondWith(
      caches.match(event.request).then((response) => {
        return response || fetch(event.request);
      })
    );
  }
});
