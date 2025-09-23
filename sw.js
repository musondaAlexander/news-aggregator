// Minimal service worker: cache shell assets
const CACHE_NAME = 'newshub-shell-v1';
const ASSETS = [
  '/',
  '/index.php',
  '/assets/css/style.css',
  '/assets/images/placeholder.svg'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(resp => resp || fetch(event.request))
  );
});
