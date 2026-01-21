/**
 * Service Worker para Organograma PWA
 * Cache de recursos e funcionalidades offline
 */

const CACHE_NAME = 'organograma-v1';
const urlsToCache = [
  '/organograma_new/index2.php',
  '/organograma_new/css/organograma.css',
  '/organograma_new/js/organograma.js',
  '/organograma_new/manifest.json',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap'
];

// Instalação do Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Ativação do Service Worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Interceptação de requisições
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - retorna resposta
        if (response) {
          return response;
        }

        // Clona a requisição
        const fetchRequest = event.request.clone();

        return fetch(fetchRequest).then(response => {
          // Verifica se recebeu uma resposta válida
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clona a resposta
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });

          return response;
        });
      })
  );
});

// Sincronização em background (se necessário)
self.addEventListener('sync', event => {
  if (event.tag === 'sync-organograma') {
    event.waitUntil(syncOrganograma());
  }
});

function syncOrganograma() {
  // Implementar sincronização de dados se necessário
  return Promise.resolve();
}

// Notificações push (se necessário)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Nova atualização no organograma',
    icon: '/assets/img/logo/logo-cores.png',
    badge: '/assets/img/logo/logo-cores.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  };

  event.waitUntil(
    self.registration.showNotification('Organograma', options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/organograma_new/index2.php')
  );
});