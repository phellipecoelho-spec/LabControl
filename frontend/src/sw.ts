/// <reference lib="webworker" />

import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { registerRoute, setDefaultHandler } from 'workbox-routing'
import { NetworkFirst, CacheFirst, NetworkOnly, StaleWhileRevalidate } from 'workbox-strategies'
import { BackgroundSyncPlugin } from 'workbox-background-sync'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'
import { ExpirationPlugin } from 'workbox-expiration'
import { clientsClaim, skipWaiting } from 'workbox-core'

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

declare let self: ServiceWorkerGlobalScope

clientsClaim()
skipWaiting()

// ---------------------------------------------------------------------------
// Precache
// ---------------------------------------------------------------------------

precacheAndRoute(self.__WB_MANIFEST)
cleanupOutdatedCaches()

// ---------------------------------------------------------------------------
// Background Sync — queue failed API mutations for later retry (24h TTL)
// ---------------------------------------------------------------------------

const bgSyncPlugin = new BackgroundSyncPlugin('apiQueue', {
  maxRetentionTime: 24 * 60, // 24 hours in minutes
})

// ---------------------------------------------------------------------------
// API routes
// ---------------------------------------------------------------------------

// GET /api/v1/* → NetworkFirst with IndexedDB as cache backing
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api/v1/') && request.method === 'GET',
  new NetworkFirst({
    cacheName: 'api-cache',
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
      new ExpirationPlugin({ maxEntries: 200, maxAgeSeconds: 30 * 24 * 60 * 60 }),
    ],
  }),
)

// POST /api/v1/* → NetworkOnly with BackgroundSync fallback
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api/v1/') && request.method === 'POST',
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
)

// PUT /api/v1/* → NetworkOnly with BackgroundSync fallback
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api/v1/') && request.method === 'PUT',
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
)

// PATCH /api/v1/* → NetworkOnly with BackgroundSync fallback
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api/v1/') && request.method === 'PATCH',
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
)

// DELETE /api/v1/* → NetworkOnly with BackgroundSync fallback
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api/v1/') && request.method === 'DELETE',
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
)

// ---------------------------------------------------------------------------
// Static assets — styles and scripts
// ---------------------------------------------------------------------------

registerRoute(
  ({ request }) =>
    request.destination === 'style' || request.destination === 'script',
  new StaleWhileRevalidate({
    cacheName: 'static-resources',
    plugins: [
      new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 30 * 24 * 60 * 60 }),
    ],
  }),
)

// ---------------------------------------------------------------------------
// Assets — images and fonts
// ---------------------------------------------------------------------------

registerRoute(
  ({ request }) =>
    request.destination === 'image' || request.destination === 'font',
  new CacheFirst({
    cacheName: 'assets',
    plugins: [
      new CacheableResponsePlugin({ statuses: [0, 200] }),
      new ExpirationPlugin({ maxEntries: 200, maxAgeSeconds: 30 * 24 * 60 * 60 }),
    ],
  }),
)

// ---------------------------------------------------------------------------
// Default handler — navigation pages
// ---------------------------------------------------------------------------

setDefaultHandler(
  new NetworkFirst({ cacheName: 'pages' }),
)

export {} // required to make this a module for TypeScript
