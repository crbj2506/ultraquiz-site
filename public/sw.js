const CACHE_NAME = 'jwquiz-cache-v1';
const urlsToCache = [
  '/',
  '/img/logo_jwquiz.png'
];

// Install event: cache basic assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch event: Network first, cache fallback approach
// We want to avoid aggressive caching so multiplayer features (WebSockets/live updates) don't break
self.addEventListener('fetch', event => {
  // Ignore non-GET requests and WebSockets
  if (event.request.method !== 'GET') return;
  
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});
