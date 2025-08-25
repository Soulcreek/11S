// Service Worker for 11Seconds Quiz Game
// Online-first for HTML, cache-first for assets. Forces fast activation on update.

const CACHE_NAME = '11seconds-v2.0.1';
const urlsToCache = [
  '/',
  '/manifest.json',
  '/favicon.ico'
  // Note: hashed assets from the build are cached on first fetch (see fetch handler)
];

// Install event - cache important files
self.addEventListener('install', event => {
  console.log('11Seconds SW: Installing (v2)...');
  // Take over as soon as installed
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('11Seconds SW: Pre-caching core files');
        return cache.addAll(urlsToCache.map(url => new Request(url, { credentials: 'same-origin' })));
      })
      .catch(err => {
        console.log('11Seconds SW: Cache failed', err);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('11Seconds SW: Activating (v2)...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('11Seconds SW: Deleting old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  // Only handle same-origin GETs
  if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) return;

  // Network-first for HTML navigations (avoid stale SPA shell)
  if (event.request.mode === 'navigate' || (event.request.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const respClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, respClone)).catch(() => {});
          return response;
        })
        .catch(async () => {
          const cached = await caches.match(event.request);
          if (cached) return cached;
          // Fallback to cached index.html when offline
          return caches.match('/') || Response.error();
        })
    );
    return;
  }

  // Cache-first for assets with background refresh (stale-while-revalidate)
  event.respondWith(
    caches.match(event.request).then(cached => {
      const fetchPromise = fetch(event.request)
        .then(response => {
          if (response && response.status === 200 && (response.type === 'basic' || response.type === 'cors')) {
            const respClone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, respClone)).catch(() => {});
          }
          return response;
        })
        .catch(() => cached);
      return cached || fetchPromise;
    })
  );
});

// Background sync for game data (when back online)
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    console.log('11Seconds SW: Background sync triggered');
    event.waitUntil(
      // Here you could sync game data when back online
      Promise.resolve()
    );
  }
});

// Push notifications (future feature)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    console.log('11Seconds SW: Push message received', data);

    const options = {
      body: data.body || 'New challenge available!',
      icon: '/logo192.png',
      badge: '/favicon.ico',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey || '1'
      }
    };

    event.waitUntil(
      self.registration.showNotification(data.title || '11Seconds Quiz', options)
    );
  }
});
