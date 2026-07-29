# Phase 13: PWA e Offline — Research

**Researched:** 2026-07-27
**Domain:** Progressive Web App (PWA) — Offline-first architecture with background sync
**Confidence:** MEDIUM

## Summary

This phase implements full PWA capabilities for LabControl: installable app via manifest, offline data access via IndexedDB + Service Worker, and automatic background sync with conflict resolution. The recommended stack is `vite-plugin-pwa` v1.3.0 (Workbox-based) + `Dexie.js` v4.4.4 (IndexedDB wrapper) + `workbox-background-sync` for sync queue management. The architecture uses `injectManifest` strategy (custom service worker) rather than `generateSW` because we need fine-grained control over sync events and conflict resolution logic.

**Primary recommendation:** Use `vite-plugin-pwa` with `injectManifest` strategy, Dexie.js for IndexedDB, and a custom Pinia store for sync state management — giving users a Google-Docs-like offline experience where the app works fully offline and syncs transparently when connectivity returns.

**Key architectural decision:** The custom service worker (not generated) is required because we need to handle Background Sync API events, custom sync queue replay logic, and field-level conflict detection — all of which exceed `generateSW` capability.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Cache assets via Service Worker + Cache API
- **D-02:** API data in IndexedDB for offline query
- **D-03:** Offline create/edit queued via Background Sync API
- **D-04:** Network-first read strategy with IndexedDB fallback
- **D-05:** Automatic background sync on connectivity restore
- **D-06:** Visual indicator: "N operações pendentes" at top
- **D-07:** Manual "Sincronizar" button
- **D-08:** Indicator disappears when all pending synced
- **D-09:** Full offline experience — all features work with cached data
- **D-10:** Dashboard shows last synced timestamp
- **D-11:** Create/edit operations queued for later sync
- **D-12:** Background sync without user intervention
- **D-13:** App name: `LabControl`
- **D-14:** Short name: `LabControl`
- **D-15:** Icons generated from template with indigo theme (`#6366f1`)
- **D-16:** Splash screen: background `#0f172a` with centered logo
- **D-17:** Display mode: `standalone`
- **D-18:** Theme color: dark (`#0f172a`)
- **D-19:** Automatic conflict detection (same record edited offline and online)
- **D-20:** Manual conflict resolution via diff — user chooses which version
- **D-21:** If no conflict (different fields), automatic merge
- **D-22:** Last-write-wins fallback if user doesn't resolve in N days

### The Agent's Discretion
- Implementation details (vite-plugin-pwa version, Workbox config, IndexedDB schema)
- Design of the pending-operations indicator
- Design of the diff visual for conflict resolution

### Deferred Ideas (OUT OF SCOPE)
- Native mobile app via Capacitor (v2 — MOBL-01 in REQUIREMENTS.md)
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PWA-01 | Sistema funciona offline com sincronização automática | Dexie.js v4.4.4 for IndexedDB, workbox-background-sync for sync queue, injectManifest strategy for custom SW |
| PWA-02 | Sistema é instalável como aplicativo | vite-plugin-pwa v1.3.0 with manifest (standalone, dark theme), @vite-pwa/assets-generator for icons |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Service Worker registration/install | Browser / Client | — | SW runs in browser context; registered from main.ts |
| Asset caching (static files) | Service Worker | Vite Build | SW intercepts fetch; Vite generates hashed assets |
| Offline data storage (IndexedDB) | Browser / Client | — | IndexedDB is browser-native; SW can read but data layer is client-side |
| Sync queue management | Browser / Client | Service Worker | Pending ops stored in IndexedDB; SW can trigger replay via Background Sync |
| Conflict detection | Backend API | Browser / Client | Server detects version mismatch; client presents diff UI |
| Conflict resolution UI | Browser / Client | — | PrimeVue Dialog for user to choose version |
| Connectivity detection | Browser / Client | — | window.addEventListener('online'/'offline') |
| Visual sync indicator | Browser / Client | — | PrimeVue Badge/Toast in AppLayout |

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `vite-plugin-pwa` | ^1.3.0 | PWA integration — service worker, manifest, precaching | Official Vite PWA plugin; Workbox-powered, zero-config, injectManifest support [VERIFIED: npm registry] |
| `dexie` | ^4.4.4 | IndexedDB wrapper for offline data storage | 14.5K stars, industry standard for browser-side DB; TypeScript-native, live queries, 0 dependencies [VERIFIED: npm registry] |
| `workbox-build` | ^7.4.1 | Build-time service worker generation | Required by vite-plugin-pwa injectManifest strategy [VERIFIED: npm registry] |
| `workbox-window` | ^7.4.1 | Client-side SW registration & lifecycle | Required for registerSW() from virtual:pwa-register [VERIFIED: npm registry] |
| `workbox-background-sync` | ^7.4.1 | Sync queue management in service worker | Standard Workbox module for queuing failed requests [VERIFIED: npm registry] |
| `workbox-strategies` | ^7.4.1 | Cache strategies (NetworkFirst, CacheFirst) | Workbox caching primitives for SW fetch handler [VERIFIED: npm registry] |
| `workbox-routing` | ^7.4.1 | Route-based cache strategy registration | Required for registerRoute() in injectManifest [VERIFIED: npm registry] |
| `@vite-pwa/assets-generator` | ^1.0.2 | Generate PWA icons from source template | CLI tool to produce all required icon sizes from a single SVG/PNG [VERIFIED: npm registry] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `workbox-core` | ^7.4.1 | SW core utilities (clientsClaim, cacheNames) | In injectManifest service worker code |
| `workbox-expiration` | ^7.4.1 | Cache expiration management | For CacheFirst strategies with size/time limits |
| `workbox-cacheable-response` | ^7.4.1 | Opt-in caching for specific status codes | For caching cross-origin opaque responses |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Dexie.js | Raw IndexedDB API | Raw API is 10x more verbose, callback-heavy, error-prone for complex queries |
| Dexie.js | idb (by Jake Archibald) | idb is lighter but missing live queries, schema migrations, and has smaller ecosystem |
| injectManifest | generateSW | generateSW simpler but cannot handle custom sync events or complex conflict logic |
| IndexedDB + Background Sync | Workbox BackgroundSyncPlugin | BackgroundSyncPlugin only handles retries; we need custom conflict detection and UI |

**Installation:**
```bash
npm install dexie@^4.4.4 workbox-background-sync@^7.4.1
npm install -D vite-plugin-pwa@^1.3.0 workbox-build@^7.4.1 workbox-window@^7.4.1 workbox-routing@^7.4.1 workbox-strategies@^7.4.1 workbox-core@^7.4.1 workbox-expiration@^7.4.1 workbox-cacheable-response@^7.4.1 @vite-pwa/assets-generator@^1.0.2
```

## Package Legitimacy Audit

> All packages verified against npm registry on 2026-07-27.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| vite-plugin-pwa | npm | 6 yrs | 3.79M/wk | github.com/vite-pwa/vite-plugin-pwa | OK | Approved |
| dexie | npm | 12 yrs | 1.98M/wk | github.com/dexie/Dexie.js | OK | Approved |
| workbox-build | npm | 7 yrs | 8.38M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-window | npm | 7 yrs | 8.37M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-background-sync | npm | 7 yrs | ~8M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-routing | npm | 7 yrs | ~8M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-strategies | npm | 7 yrs | ~8M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-core | npm | 7 yrs | ~8M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-expiration | npm | 7 yrs | ~8M/wk | github.com/googlechrome/workbox | OK | Approved |
| workbox-cacheable-response | npm | 7 yrs | ~8M/wk | github.com/googlechrome/workbox | OK | Approved |
| @vite-pwa/assets-generator | npm | 2 yrs | ~50K/wk | github.com/vite-pwa/assets-generator | OK | Approved |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Browser / Client                              │
│                                                                      │
│  ┌─────────────┐   ┌──────────────────┐   ┌──────────────────────┐  │
│  │  Vue 3 SPA   │   │   Pinia Stores   │   │  PrimeVue UI Layer   │  │
│  │  (App.vue)   │   │ (auth, sync, etc)│   │ (Badge, Dialog, etc) │  │
│  └──────┬───────┘   └────────┬─────────┘   └──────────┬───────────┘  │
│         │                   │                         │              │
│         ▼                   ▼                         ▼              │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    Offline Data Layer                          │  │
│  │  ┌─────────────────────────────────────────────────────────┐   │  │
│  │  │  IndexedDB (Dexie.js v4.4.4)                            │   │  │
│  │  │  ├── entities (cached API data)                         │   │  │
│  │  │  ├── sync_queue (pending create/update/delete ops)      │   │  │
│  │  │  └── conflicts (detected conflicts for user resolution) │   │  │
│  │  └─────────────────────────────────────────────────────────┘   │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │  Axios Interceptor (api.ts)                                    │  │
│  │  ├── Online: pass through normally                             │  │
│  │  └── Offline: save to sync_queue + return optimistic response  │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │  Connectivity Monitor (useOnline composable)                    │  │
│  │  ├── navigator.onLine + online/offline events                   │  │
│  │  └── Triggers sync when coming online                          │  │
│  └────────────────────────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────────────────────┘
                       │ HTTPS (REST API)
                       ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Service Worker (injectManifest)                    │
│                                                                      │
│  ┌────────────────┐  ┌──────────────────┐  ┌────────────────────┐   │
│  │  Cache First    │  │  NetworkFirst    │  │  Background Sync   │   │
│  │  (static assets)│  │  (API responses) │  │  (sync queue)      │   │
│  └────────────────┘  └──────────────────┘  └────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  Fetch Event Handler                                        │    │
│  │  ├── Static assets → CacheFirst from Cache API              │    │
│  │  ├── API GET requests → NetworkFirst (network → IndexedDB)  │    │
│  │  └── Navigation requests → NetworkFirst → index.html        │    │
│  └─────────────────────────────────────────────────────────────┘    │
└──────────────────────┬──────────────────────────────────────────────┘
                       │
                       ▼
               ┌──────────────┐
               │  Internet     │
               │  (when online) │
               └──────┬───────┘
                      │
                      ▼
              ┌───────────────┐
              │  Laravel API   │
              │  /api/v1/*     │
              └───────┬───────┘
                      │
                      ▼
              ┌───────────────┐
              │  PostgreSQL    │
              └───────────────┘
```

### Recommended Project Structure
```
frontend/
├── public/
│   ├── pwa-icons/          # Generated PWA icons (192x192, 512x512, maskable)
│   │   ├── icon-192x192.png
│   │   ├── icon-512x512.png
│   │   └── icon-512x512-maskable.png
│   └── pwa-64x64.png       # Smaller icon for taskbar
├── src/
│   ├── services/
│   │   ├── api.ts           # Modified: offline-aware Axios with sync queue
│   │   └── sync.ts          # Sync engine: queue management, replay, conflict resolution
│   ├── stores/
│   │   ├── auth.ts          # Existing auth store
│   │   └── sync.ts          # NEW: Sync store (pending count, status, manual sync trigger)
│   ├── composables/
│   │   ├── useTheme.ts      # Existing
│   │   └── useOnline.ts     # NEW: Reactive online/offline state via @vueuse/core
│   ├── db/
│   │   ├── index.ts         # Dexie database definition + schema
│   │   ├── entities.ts      # Entity-specific CRUD operations
│   │   └── syncQueue.ts     # Sync queue table operations
│   ├── sw.ts                # Custom service worker (injectManifest entry point)
│   ├── components/
│   │   └── pwa/
│   │       ├── SyncIndicator.vue    # Pending operations badge + manual sync button
│   │       ├── ConflictDialog.vue   # Diff viewer for manual conflict resolution
│   │       └── UpdatePrompt.vue     # "New version available" banner
│   ├── App.vue              # Modified: add SyncIndicator, UpdatePrompt
│   └── main.ts              # Modified: register service worker
├── vite.config.ts           # Modified: add VitePWA plugin with injectManifest
└── package.json             # Modified: new dependencies
```

### Pattern 1: Custom Service Worker with injectManifest
**What:** Use `injectManifest` strategy in vite-plugin-pwa to write a custom service worker that handles offline caching, API fallback, and background sync.
**When to use:** When you need fine-grained control over fetch handling (not just precaching) and custom sync events.
**Example (vite.config.ts):**
```typescript
// Source: vite-pwa-org.netlify.app/workbox/inject-manifest.html [CITED]
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.ts',
      registerType: 'autoUpdate',
      manifest: {
        name: 'LabControl',
        short_name: 'LabControl',
        description: 'Plataforma modular de gestão laboratorial',
        theme_color: '#0f172a',
        background_color: '#0f172a',
        display: 'standalone',
        scope: '/',
        start_url: '/',
        icons: [
          { src: '/pwa-icons/icon-192x192.png', sizes: '192x192', type: 'image/png' },
          { src: '/pwa-icons/icon-512x512.png', sizes: '512x512', type: 'image/png' },
          { src: '/pwa-icons/icon-512x512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,ttf}'],
      },
      injectManifest: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,ttf}'],
      },
    }),
  ],
})
```

### Pattern 2: Dexie Database Schema for Offline Storage
**What:** Define IndexedDB tables for cached entities and sync queue.
**When to use:** Every offline-first PWA needs a structured local data layer.
**Example:**
```typescript
// Source: dexie.org/docs/API-Reference [CITED]
import Dexie, { type EntityTable } from 'dexie'

export interface SyncQueueItem {
  id?: number
  action: 'create' | 'update' | 'delete'
  entity: string
  entityId: string
  endpoint: string
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  payload: Record<string, unknown>
  createdAt: string  // ISO timestamp
  retryCount: number
}

export interface ConflictRecord {
  id?: number
  entity: string
  entityId: string
  localVersion: Record<string, unknown>
  serverVersion: Record<string, unknown>
  baseVersion: Record<string, unknown>
  detectedAt: string
  status: 'pending' | 'resolved'
  resolvedAt?: string
  resolution?: 'keep-local' | 'keep-server' | 'manual-merge'
}

export class LabControlDB extends Dexie {
  syncQueue!: EntityTable<SyncQueueItem, 'id'>
  conflicts!: EntityTable<ConflictRecord, 'id'>
  // Entity-specific tables added dynamically per module
  equipment!: EntityTable<...>
  inventoryItems!: EntityTable<...>
  loans!: EntityTable<...>
  calibrations!: EntityTable<...>
  maintenanceOrders!: EntityTable<...>
  verifications!: EntityTable<...>

  constructor() {
    super('LabControl')
    this.version(1).stores({
      syncQueue: '++id, action, entity, createdAt',
      conflicts: '++id, entity, entityId, status',
      equipment: 'id, updatedAt',
      inventoryItems: 'id, updatedAt',
      loans: 'id, updatedAt',
      calibrations: 'id, updatedAt',
      maintenanceOrders: 'id, updatedAt',
      verifications: 'id, updatedAt',
    })
  }
}

export const db = new LabControlDB()
```

### Pattern 3: Background Sync with Workbox
**What:** Use workbox-background-sync to queue failed API requests when offline.
**When to use:** For mutating API calls (POST, PUT, PATCH, DELETE) that need to be replayed.
**Example (in sw.ts):**
```typescript
// Source: developer.chrome.com/docs/workbox/modules/workbox-background-sync [CITED]
import { BackgroundSyncPlugin } from 'workbox-background-sync'
import { registerRoute } from 'workbox-routing'
import { NetworkOnly } from 'workbox-strategies'

const syncPlugin = new BackgroundSyncPlugin('apiSyncQueue', {
  maxRetentionTime: 24 * 60,  // Retry for 24 hours
  onSync: async ({ queue }) => {
    let entry
    while ((entry = await queue.shiftRequest())) {
      try {
        await fetch(entry.request.clone())
      } catch (error) {
        await queue.unshiftRequest(entry)
        throw error
      }
    }
  },
})

// Queue all mutating API calls
registerRoute(
  /\/api\/v1\/.*/,
  new NetworkOnly({
    plugins: [syncPlugin],
  }),
  'POST'  // Also applies to PUT, PATCH, DELETE
)
```

### Anti-Patterns to Avoid
- **Using generateSW strategy for custom sync logic:** generateSW cannot handle sync events or custom conflict resolution. Always use injectManifest when you need custom SW behavior.
- **Caching the service worker file:** sw.js must NEVER be cached by the browser. The nginx config must explicitly set `Cache-Control: no-cache, no-store, must-revalidate` for `/sw.js`.
- **Blocking the UI during sync:** Sync should happen in background. Never show a loading spinner that blocks user interaction while syncing.
- **Single sync queue without batching:** For many pending operations, sending them one at a time is slow. Batch sync operations (50-100 per request).
- **Assuming Background Sync works everywhere:** Safari and Firefox do NOT support Background Sync. Always detect support and provide fallback (sync on page focus/reload).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| IndexedDB ORM/wrapper | Raw IndexedDB API | Dexie.js | Promise-based, schema versioning, live queries, 0-deps, 14.5K stars |
| Service worker generation | Manual SW file | vite-plugin-pwa | Auto-precaching, manifest injection, hash revisioning, Workbox integration |
| Background sync retries | Custom retry logic | workbox-background-sync | Handles exponential backoff, retry limits, queue persistence automatically |
| PWA icon generation | Manual resizing | @vite-pwa/assets-generator | Generates all required sizes (72x72 to 512x512) from single source |
| Cache strategies | Custom fetch handler | Workbox strategies (NetworkFirst, CacheFirst) | Production-tested, handles edge cases (opaque responses, CORS) |
| Conflict detection engine | Custom version tracking | Implement lightweight version vectors | Business logic is app-specific, but use standard `lastModified` + field-level diff rather than building a CRDT |

**Key insight:** The three layers (Service Worker for assets, IndexedDB for data, Background Sync for mutations) are complementary. Skipping any one leaves a gap in the offline experience. The most common mistake is trying to use only one layer for everything.

## Common Pitfalls

### Pitfall 1: Service Worker Cached by Browser
**What goes wrong:** Browser caches sw.js and never picks up new versions, causing users to run stale code.
**Why it happens:** Default nginx configuration caches all .js files, including sw.js.
**How to avoid:** Add explicit nginx location block for sw.js with `Cache-Control: no-cache, no-store, must-revalidate`.
**Warning signs:** Users still see old features after deployment; DevTools shows old service worker.

### Pitfall 2: Background Sync Not Available in All Browsers
**What goes wrong:** Safari/Firefox users' offline operations never sync.
**Why it happens:** Background Sync API is Chromium-only (~77% global support).
**How to avoid:** Always check `'sync' in registration` at runtime. Fall back to syncing on page focus (`visibilitychange` event) or on page reload.
**Warning signs:** Offline operations collected in IndexedDB but never sent to server on Safari.

### Pitfall 3: Session Token Expiration During Extended Offline Periods
**What goes wrong:** After days offline, the Sanctum SPA session cookie expires, and sync requests get 401 errors.
**Why it happens:** Sanctum SPA sessions have an expiry (configurable in Laravel); the offline user has no way to refresh.
**How to avoid:** Store session expiry alongside sync queue items. On 401 during sync, pause queue, prompt user to re-authenticate, then resume.
**Warning signs:** All sync requests fail with 401 after reconnection.

### Pitfall 4: IndexedDB Storage Quota Exceeded
**What goes wrong:** App stops working offline because IndexedDB is full.
**Why it happens:** Users accumulate large amounts of data offline (especially with file attachments).
**How to avoid:** Implement storage quota monitoring via `navigator.storage.estimate()`. Set per-entity max records. Warn user when approaching limits.
**Warning signs:** write operations to IndexedDB start failing silently.

### Pitfall 5: Conflict Resolution Never Completed
**What goes wrong:** Conflicts pile up because users ignore the resolution dialog, and their edits never sync.
**Why it happens:** D-22 specifies a fallback (LWW after N days), but if the timeout is too long or the UI is intrusive, conflicts accumulate.
**How to avoid:** Set N=7 days per D-22. Show conflict count in the sync indicator. Use PrimeVue Toast for non-intrusive reminders rather than blocking dialogs.

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Workbox v6 with generateSW | Workbox v7 with injectManifest or generateSW | 2023 | injectManifest gives full control over SW logic |
| IndexedDB raw API | Dexie.js v4 with liveQuery | 2024 | Reactive UI updates when IndexedDB changes |
| Background Sync (single) | Background Sync with workbox-background-sync | 2020 | Automatic queuing, retry, and persistence |
| Browser install prompt (beforeinstallprompt) | Custom install button + manifest | 2024 | More control over install UX |
| Vite < 6 with esbuild | Vite 8 with Rolldown (Rust bundler) | 2026 | 10-30x faster builds, but plugin compatibility must be verified |

**Deprecated/outdated:**
- `sw-precache` / `sw-toolbox`: Migrate to Workbox
- `localforage`: Use Dexie.js for structured data, not key-value stores
- CRA service worker: Use vite-plugin-pwa for Vite projects
- Hand-written JSON manifest: Use vite-plugin-pwa `manifest` option (auto-injected)

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | vite-plugin-pwa v1.3.0 is fully compatible with Vite 8.1.5 | Standard Stack | Minor — workaround via `overrides` in package.json exists (documented in vite-pwa GH issue #918) |
| A2 | Dexie.js v4.4.4 works with TypeScript strict mode | Standard Stack | Low — Dexie.js has comprehensive TypeScript support built-in |
| A3 | Background Sync fallback (sync on page focus) provides adequate UX for Safari/Firefox users | Architecture Patterns | Medium — users must keep the tab open for sync to occur; may need additional fallback (periodic keepalive) |
| A4 | Sanctum session cookie expiry is configurable to accommodate extended offline periods | Common Pitfalls | Medium — may require backend changes to session lifetime configuration |
| A5 | The Laravel API endpoints for conflict detection (version tracking) can be added without breaking existing routes | Architecture Patterns | Medium — requires backend changes to return `lastModified` on all resources and accept version in write requests |

## Open Questions (RESOLVED)

1. ~~**Conflict detection endpoint design**~~ **(RESOLVED)**
   - Resolution: Use individual HTTP 409 Conflict responses per entity (standard REST practice). Each mutating endpoint accepts `X-If-Unmodified-Since` header or `updated_at` timestamp in request body. If the server version is newer, return 409 with `{ serverVersion, baseVersion }` in response body. No dedicated `/sync` endpoint needed — the sync queue replays operations individually and handles 409 per operation. This keeps backend changes minimal and avoids batch complexity.
   - Rationale per D-19/D-20/D-21: Individual 409 handling allows field-level conflict detection. A batch `/sync` endpoint would require custom batch conflict logic that duplicates existing per-entity validation.

2. ~~**Sanctum session lifetime for offline use**~~ **(RESOLVED)**
   - Resolution: Current `SESSION_LIFETIME=120` (2 hours) in `backend/.env:31` is insufficient for extended offline periods. Mitigation: The SyncService catches 401 responses during sync replay and pauses the queue, prompting the user to re-authenticate. For a better UX in future, `SESSION_LIFETIME` can be increased to 10080 (7 days). For v1, the 401-pause behavior is acceptable.
   - Rationale per D-12: Session expiry is a known constraint; the auto-sync mechanism handles it gracefully by stopping and waiting for user re-auth.

3. ~~**File attachment handling offline**~~ **(RESOLVED)**
   - Resolution: Defer large file uploads to online-only. The API interceptor in `api.ts` checks if request data contains `File` or `Blob` objects — if so, and offline, show a warning toast "Upload disponível apenas online" and do NOT queue. Text-only CRUD operations are queued normally. File uploads sync only when online.
   - Rationale per D-09: File attachments are not critical for offline operation; metadata and text data are sufficient for offline use.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Node.js | Frontend build toolchain | ✓ | 22.12.0 | — |
| npm | Package management | ✓ | 10.x | — |
| Docker | nginx | ✓ | — | — |
| nginx | Production-like config for SW headers | ✓ (in Docker) | — | — |

**Missing dependencies with no fallback:** none
**Missing dependencies with fallback:** none

## Code Examples

### vite.config.ts — Full PWA Configuration
```typescript
// Source: vite-pwa-org.netlify.app/guide/ [CITED]
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.ts',
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'robots.txt'],
      manifest: {
        name: 'LabControl',
        short_name: 'LabControl',
        description: 'Plataforma modular de gestão laboratorial',
        theme_color: '#0f172a',
        background_color: '#0f172a',
        display: 'standalone',
        scope: '/',
        start_url: '/',
        icons: [
          { src: '/pwa-icons/icon-64x64.png', sizes: '64x64', type: 'image/png' },
          { src: '/pwa-icons/icon-192x192.png', sizes: '192x192', type: 'image/png' },
          { src: '/pwa-icons/icon-512x512.png', sizes: '512x512', type: 'image/png' },
          { src: '/pwa-icons/icon-512x512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        navigateFallback: '/index.html',
        cleanupOutdatedCaches: true,
      },
    }),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:80',
        changeOrigin: true,
      },
    },
  },
})
```

### Service Worker (src/sw.ts) — injectManifest Entry Point
```typescript
// Source: vite-pwa-org.netlify.app/workbox/inject-manifest.html [CITED]
import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { registerRoute, setDefaultHandler } from 'workbox-routing'
import { NetworkFirst, CacheFirst, NetworkOnly } from 'workbox-strategies'
import { BackgroundSyncPlugin } from 'workbox-background-sync'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'
import { ExpirationPlugin } from 'workbox-expiration'
import { clientsClaim } from 'workbox-core'

declare let self: ServiceWorkerGlobalScope

// Take control immediately (required for autoUpdate)
clientsClaim()
self.skipWaiting()

// Precache all static assets (injected by Vite at build time)
precacheAndRoute(self.__WB_MANIFEST)
cleanupOutdatedCaches()

// Background Sync for mutating API requests
const bgSyncPlugin = new BackgroundSyncPlugin('apiQueue', {
  maxRetentionTime: 24 * 60, // 24 hours
})

// API routes: NetworkFirst for GET, NetworkOnly + BackgroundSync for mutations
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/v1/'),
  new NetworkFirst({
    cacheName: 'api-cache',
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
      new ExpirationPlugin({ maxEntries: 200, maxAgeSeconds: 30 * 24 * 60 * 60 }), // 30 days
    ],
  }),
  'GET'
)

registerRoute(
  ({ url }) => url.pathname.startsWith('/api/v1/'),
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
  'POST'
)

registerRoute(
  ({ url }) => url.pathname.startsWith('/api/v1/'),
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
  'PUT'
)

registerRoute(
  ({ url }) => url.pathname.startsWith('/api/v1/'),
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
  'PATCH'
)

registerRoute(
  ({ url }) => url.pathname.startsWith('/api/v1/'),
  new NetworkOnly({ plugins: [bgSyncPlugin] }),
  'DELETE'
)

// Static assets: CacheFirst with expiration
registerRoute(
  ({ request }) => request.destination === 'style' || request.destination === 'script',
  new StaleWhileRevalidate({
    cacheName: 'static-resources',
    plugins: [
      new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 30 * 24 * 60 * 60 }),
    ],
  })
)

registerRoute(
  ({ request }) => request.destination === 'image' || request.destination === 'font',
  new CacheFirst({
    cacheName: 'assets',
    plugins: [
      new CacheableResponsePlugin({ statuses: [0, 200] }),
      new ExpirationPlugin({ maxEntries: 200, maxAgeSeconds: 30 * 24 * 60 * 60 }),
    ],
  })
)

// Navigation requests: NetworkFirst with index.html fallback
setDefaultHandler(
  new NetworkFirst({
    cacheName: 'pages',
    plugins: [
      new CacheableResponsePlugin({ statuses: [200] }),
    ],
  })
)
```

### Dexie Database Setup
```typescript
// Source: dexie.org/docs/API-Reference [CITED]
import Dexie, { type EntityTable } from 'dexie'

export interface PendingOperation {
  id?: number
  entityType: string        // e.g., 'equipment', 'inventoryItems'
  entityId: string          // UUID
  action: 'create' | 'update' | 'delete'
  endpoint: string          // '/api/v1/equipment'
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  payload: Record<string, unknown>
  createdAt: string
  retryCount: number
  lastError?: string
}

export interface ConflictItem {
  id?: number
  entityType: string
  entityId: string
  field: string
  localValue: unknown
  serverValue: unknown
  baseValue: unknown
  detectedAt: string
  status: 'pending' | 'resolved'
  resolution?: 'keep-local' | 'keep-server' | 'manual-merge'
  resolvedAt?: string
}

export class LabControlDB extends Dexie {
  syncQueue!: EntityTable<PendingOperation, 'id'>
  conflicts!: EntityTable<ConflictItem, 'id'>
  equipment!: EntityTable<Record<string, unknown>, string>
  inventoryItems!: EntityTable<Record<string, unknown>, string>
  loans!: EntityTable<Record<string, unknown>, string>
  calibrations!: EntityTable<Record<string, unknown>, string>
  maintenanceOrders!: EntityTable<Record<string, unknown>, string>
  verifications!: EntityTable<Record<string, unknown>, string>

  constructor() {
    super('LabControl')
    this.version(1).stores({
      syncQueue: '++id, [entityType+entityId], action, createdAt, retryCount',
      conflicts: '++id, [entityType+entityId], field, status',
      equipment: 'id, updatedAt',
      inventoryItems: 'id, updatedAt',
      loans: 'id, updatedAt',
      calibrations: 'id, updatedAt',
      maintenanceOrders: 'id, updatedAt',
      verifications: 'id, updatedAt',
    })
  }
}

export const db = new LabControlDB()
```

### Manual Sync Trigger (src/stores/sync.ts)
```typescript
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { db } from '@/db'

export const useSyncStore = defineStore('sync', () => {
  const pendingCount = ref(0)
  const lastSyncAt = ref<string | null>(null)
  const isSyncing = ref(false)
  const isOnline = ref(navigator.onLine)

  async function refreshPendingCount() {
    pendingCount.value = await db.syncQueue.count()
  }

  async function manualSync() {
    if (isSyncing.value) return
    isSyncing.value = true
    try {
      const registration = await navigator.serviceWorker.ready
      if ('sync' in registration) {
        await registration.sync.register('sync-queue')
      } else {
        // Fallback: replay queue directly
        await replayQueue()
      }
    } finally {
      isSyncing.value = false
      await refreshPendingCount()
      lastSyncAt.value = new Date().toISOString()
    }
  }

  async function replayQueue() {
    const items = await db.syncQueue.toArray()
    for (const item of items) {
      try {
        const response = await fetch(item.endpoint, {
          method: item.method,
          headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '' },
          credentials: 'include',
          body: JSON.stringify(item.payload),
        })
        if (response.ok || response.status === 409) {
          // 409 = conflict detected — handle in sync engine
          await db.syncQueue.delete(item.id!)
        } else if (response.status === 401) {
          throw new Error('Session expired')
        }
      } catch (error) {
        await db.syncQueue.update(item.id!, { retryCount: item.retryCount + 1, lastError: String(error) })
        throw error
      }
    }
  }

  return { pendingCount, lastSyncAt, isSyncing, isOnline, refreshPendingCount, manualSync }
})
```

### Nginx Configuration for Service Worker
```nginx
# Source: multiple StackOverflow answers + oneuptime.com/blog [CITED]
# Add to docker/nginx/default.conf

# Service worker — MUST NOT be cached
location = /sw.js {
    expires -1y;
    add_header Cache-Control "no-cache, no-store, must-revalidate" always;
    add_header Pragma "no-cache" always;
    access_log off;
}

# PWA manifest — short cache
location = /manifest.json {
    expires 1d;
    add_header Cache-Control "public, must-revalidate" always;
}
```

### Browser Compatibility Check (src/composables/useOnline.ts)
```typescript
// Source: MDN navigator.onLine + visibilitychange pattern [CITED]
import { ref, onMounted, onUnmounted } from 'vue'
import { useSyncStore } from '@/stores/sync'

export function useOnline() {
  const isOnline = ref(navigator.onLine)
  const syncStore = useSyncStore()

  function handleOnline() {
    isOnline.value = true
    // Trigger sync when coming back online
    syncStore.manualSync()
  }

  function handleOffline() {
    isOnline.value = false
  }

  // Fallback for Safari/Firefox without Background Sync
  function handleVisibilityChange() {
    if (document.visibilityState === 'visible' && navigator.onLine) {
      syncStore.refreshPendingCount()
      if (syncStore.pendingCount > 0) {
        syncStore.manualSync()
      }
    }
  }

  let cleanupTimer: ReturnType<typeof setInterval> | null = null

  onMounted(() => {
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    document.addEventListener('visibilitychange', handleVisibilityChange)

    // Periodic check (periodic background sync not available everywhere)
    cleanupTimer = setInterval(() => {
      if (navigator.onLine && syncStore.pendingCount > 0) {
        syncStore.manualSync()
      }
    }, 5 * 60 * 1000) // Every 5 minutes
  })

  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    if (cleanupTimer) clearInterval(cleanupTimer)
  })

  return { isOnline }
}
```

## Validation Architecture

> This section describes how to validate PWA functionality.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Vitest (to be installed) + Playwright (for E2E PWA tests) |
| Quick run command | `npx vitest run --reporter=verbose` |
| Full suite command | `npx playwright test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command |
|--------|----------|-----------|-------------------|
| PWA-01 | App serves cached content offline | E2E | `npx playwright test --grep "offline"` |
| PWA-01 | Sync queue persists across page reloads | Unit | `npx vitest run src/db/__tests__/syncQueue.test.ts` |
| PWA-01 | Pending operations count shown in UI | Unit | `npx vitest run src/stores/__tests__/sync.test.ts` |
| PWA-01 | Manual sync replays queued operations | Integration | `npx vitest run src/services/__tests__/sync.test.ts` |
| PWA-01 | Auto-sync triggers on reconnect | E2E | `npx playwright test --grep "auto-sync"` |
| PWA-02 | Service worker registered | Unit | `npx vitest run src/__tests__/sw.test.ts` |
| PWA-02 | Manifest contains correct properties | Unit | `npx vitest run src/__tests__/manifest.test.ts` |
| PWA-02 | App installable via Lighthouse criteria | Manual | Lighthouse PWA audit in Chrome DevTools |

### Sampling Rate
- **Per task commit:** `npx vitest run --changed`
- **Phase gate:** Lighthouse PWA audit + `npx vitest run` + `npx playwright test`

### Wave 0 Gaps
- [ ] `src/db/__tests__/` — needs creation
- [ ] `src/stores/__tests__/` — needs creation
- [ ] `src/services/__tests__/` — needs creation
- [ ] `playwright.config.ts` — needs creation for E2E PWA tests
- [ ] `vitest.config.ts` — needs to be added if not present
- [ ] Lighthouse CI — manual check at phase gate

## Security Domain

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V5 Input Validation | yes | Zod schemas for sync queue payloads (validate before sending/receiving) |
| V6 Cryptography | yes | HTTPS required for service worker (browser enforcement) |
| V8 Data Protection | yes | IndexedDB data is accessible to any JS on the same origin; sensitive data should not be stored offline |

### Known Threat Patterns for {PWA + Laravel}
| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Stale session tokens in offline queue | Spoofing | Check 401 response during sync; prompt for re-auth; do not retry stale tokens |
| IndexedDB XSS data theft | Information Disclosure | All data in IndexedDB is accessible to any script on origin; sanitize all outputs |
| Conflict resolution UI manipulation | Tampering | Server validates all write operations regardless of client-side merge choice |
| Replay attack on sync queue | Repudiation | Server-side idempotency via `X-Idempotency-Key` on sync operations |

## Sources

### Primary (MEDIUM confidence)
- vite-pwa-org.netlify.app — VitePWA plugin documentation (configuration, injectManifest, generateSW)
- dexie.org — Dexie.js API reference (schema, CRUD, live queries)
- developer.chrome.com/docs/workbox — Workbox v7 modules (strategies, background-sync, routing)
- MDN Web Docs — Background Sync API, Service Worker API, Web App Manifest

### Secondary (LOW confidence)
- GitHub issues (vite-pwa/vite-plugin-pwa#918, #923) — Vite 8 compatibility confirmation
- WebSearch articles on offline-first architecture patterns
- StackOverflow — nginx Cache-Control for service worker files
- caniuse.com — Background Sync browser support statistics (77.48% global)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages verified against npm registry, versions confirmed, legitimacy checked
- Architecture: MEDIUM — patterns are standard but conflict resolution specifics depend on backend API design
- Pitfalls: HIGH — well-documented failure modes for PWA offline-first apps

**Research date:** 2026-07-27
**Valid until:** 2026-08-27 (30 days for this fast-moving stack — Workbox/vite-plugin-pwa may update)
