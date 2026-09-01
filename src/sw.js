import { precacheAndRoute } from 'workbox-precaching'

precacheAndRoute(self.__WB_MANIFEST)

self.addEventListener('push', (event) => {
  let data = {}
  try {
    data = event.data?.json() ?? {}
  } catch {
    data = { title: 'Gelatto ICE CO.', body: event.data?.text() ?? '' }
  }

  event.waitUntil(
    self.registration.showNotification(data.title ?? 'Gelatto ICE CO.', {
      body: data.body ?? '',
      icon: './icons/icon-192.png',
      badge: './icons/icon-192.png',
      tag: data.tag ?? 'gelatto-alerta',
    }),
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      if (clients.length > 0) return clients[0].focus()
      return self.clients.openWindow('./')
    }),
  )
})

self.addEventListener('activate', () => self.clients.claim())
self.skipWaiting()
