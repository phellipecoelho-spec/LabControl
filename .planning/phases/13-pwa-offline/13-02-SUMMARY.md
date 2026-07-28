---
phase: 13-pwa-offline
plan: 02
subsystem: pwa-offline
tags:
  - sync-engine
  - conflict-resolution
  - pwa-ui
  - offline
dependency_graph:
  requires:
    - 13-01 (Dexie DB, Service Worker, vite-plugin-pwa)
  provides:
    - SyncService engine (replay queue, conflict detection, auto-merge)
    - Pinia sync store (pendingCount, lastSyncAt, manualSync)
    - PWA UI components (SyncIndicator, ConflictDialog, UpdatePrompt)
    - App shell integration (main.ts, App.vue, DashboardPage)
  affects:
    - main.ts (SW registration after mount)
    - App.vue (SyncIndicator bar, ConflictDialog, UpdatePrompt, useOnline)
    - DashboardPage.vue (last sync timestamp)
    - env.d.ts (vite-plugin-pwa/client types)
tech-stack:
  added:
    - Dexie liveQuery for reactive conflict monitoring
    - virtual:pwa-register for SW lifecycle hooks
  patterns:
    - Event-based useOnline wiring (online/offline/visibility-change)
    - Field-level diff for conflict detection with auto-merge
    - Static SyncService class for queue replay
    - Dexie liveQuery subscription in App.vue for reactive conflict detection
key-files:
  created:
    - frontend/src/services/sync.ts
    - frontend/src/stores/sync.ts
    - frontend/src/components/pwa/SyncIndicator.vue
    - frontend/src/components/pwa/ConflictDialog.vue
    - frontend/src/components/pwa/UpdatePrompt.vue
  modified:
    - frontend/src/env.d.ts
    - frontend/src/main.ts
    - frontend/src/App.vue
    - frontend/src/modules/dashboard/pages/DashboardPage.vue
decisions:
  - SyncIndicator placed as inline bar inside AppLayout content area (not modifying AppTopbar.vue)
  - Conflict detection via Dexie liveQuery in App.vue (reactive subscription to db.conflicts)
  - Auto-sync on reconnect via useOnline().on('online') callback
  - registerSW called from main.ts (PROD only) and UpdatePrompt.vue (dev-aware dynamic import)
metrics:
  duration: ~25 minutes
  completed_date: 2026-07-27
  tasks: 4
  files_created: 5
  files_modified: 4
status: complete
---

# Phase 13 Plan 02: Sync Engine & PWA UI Components Summary

Complete sync engine with conflict detection, resolution, and PWA UI indicator components integrated into the LabControl app shell.

## Tasks Completed

### Task 1: Sync Service Engine and Pinia Sync Store
- **`frontend/src/services/sync.ts`** — `SyncService` static class:
  - `replayQueue()`: Replays all pending operations via `fetch()` (avoids Axios offline interceptor via `X-Sync-Engine: true` header). Handles 200/201/204 (delete from queue), 409 (conflict → store in `db.conflicts`), 401 (throw `SyncAuthError` to pause queue), 5xx (increment retry, continue), network errors (increment retry, break)
  - `detectAndStoreConflict()`: Field-level diff between local payload and server version. Auto-resolves when only server changed (local matches base). Auto-merges non-conflicting fields (different fields changed). Creates conflict records for genuine field conflicts.
  - `autoResolveStaleConflicts(days=7)`: Resolves pending conflicts older than N days via keep-server (D-22)
  - `resolveConflict(conflictId, resolution, mergedPayload?)`: Supports keep-local (PUT local to server), keep-server (update cache), manual-merge (PUT merged payload) (D-20)
  - `SyncAuthError` class for session expiry signaling
  - `getCookie()` helper for XSRF-TOKEN

- **`frontend/src/stores/sync.ts`** — `useSyncStore()` Pinia store with setup syntax:
  - State: `pendingCount`, `lastSyncAt`, `isSyncing`, `isOnline`, `hasConflicts`
  - Getters: `hasPending` (D-08), `pendingLabel` (D-06)
  - Actions: `refreshPendingCount()`, `loadLastSyncAt()`, `manualSync()` (full cycle: replay + auto-resolve + persist), `dismissConflict()`, `setOnlineStatus()`, `refreshConflicts()`
  - Auto-initializes on store creation (calls `refreshPendingCount()`, `loadLastSyncAt()`, `refreshConflicts()`)

### Task 2: PWA UI Components

- **`SyncIndicator.vue`** — PrimeVue `Tag` with severity="warn" showing pending count. "Sincronizar" button (D-07). Shows "Sincronizando..." spinner when syncing. Green Tag with relative time when synced (D-10). Topbar/inline position modes.

- **`ConflictDialog.vue`** — PrimeVue `Dialog` (~600px modal) with entity type/ID header. Field-by-field DataTable display with local values on amber-100 background and server values on blue-100 background. Three footer buttons: "Manter Local" (warn), "Manter Servidor" (info), "Fechar" (secondary). Emits `resolve` with `{ conflictId, resolution }` (D-20).

- **`UpdatePrompt.vue`** — Fixed-bottom `Message` banner via `registerSW()` from `virtual:pwa-register`. Shows "Nova versão disponível" with "Atualizar agora" and "Depois" buttons. Toast on new version detection and offline-ready event. Uses dynamic import to avoid dev-mode issues.

### Task 3: Integration into App Shell

- **`frontend/src/main.ts`** — After `app.mount('#app')`, imports and calls `registerSW()` from `virtual:pwa-register` in PROD mode (catches silently in dev).

- **`frontend/src/App.vue`** — Comprehensive integration:
  - Adds `<SyncIndicator position="inline">` as a bar inside `<AppLayout>` content area (wraps in `.app-content-wrapper`)
  - Adds `<UpdatePrompt />` and `<ConflictDialog />` at app root level
  - Imports `useOnline()` composable and wires `online` → `syncStore.manualSync()`, `offline` → `syncStore.setOnlineStatus(false)`, `visibility-change` → `syncStore.refreshPendingCount()`
  - Dexie `liveQuery` subscription watches `db.conflicts` for pending records — auto-shows ConflictDialog on new conflicts
  - `handleConflictResolve()` resolves conflict and loads next pending one

- **`frontend/src/modules/dashboard/pages/DashboardPage.vue`** — Shows "Última sincronização: {timestamp}" in toolbar with clock icon (D-10). Uses `syncStore.lastSyncAt` and `toLocaleString('pt-BR')`.

- **`frontend/src/env.d.ts`** — Added `/// <reference types="vite-plugin-pwa/client" />` for `virtual:pwa-register` type declarations.

### Task 4: Build Verification
- `vue-tsc --noEmit`: Only pre-existing errors in unrelated files (PasswordInput, LoanCreateDialog, router/index, EquipmentLogsSection)
- `vite build`: Success — 5.72s build, PWA injectManifest with 99 precached entries, SW injected from `src/sw.ts`

## Deviations from Plan

**None** — all three tasks executed as specified in the plan.

### Auto-fixed Issues

**[Rule 1 — Bug] Fixed UpdatePrompt.vue registerSW type signature**
- **Found during:** Task 2
- **Issue:** The `registerSW()` from `virtual:pwa-register` returns the update function directly (not an object with `updateServiceWorker` property) as per `vanillajs.d.ts` types
- **Fix:** Removed `SWRegistration` interface wrapper; stored the returned function directly
- **Files modified:** `frontend/src/components/pwa/UpdatePrompt.vue`
- **Commit:** `d6a8a99`

**[Rule 3 — Blocking Issue] Added type declarations for virtual:pwa-register**
- **Found during:** Task 2 typecheck
- **Issue:** `virtual:pwa-register` is a virtual module (no physical file), TypeScript had no type declarations for it
- **Fix:** Added `/// <reference types="vite-plugin-pwa/client" />` to `env.d.ts`
- **Files modified:** `frontend/src/env.d.ts`
- **Commit:** `d6a8a99`

**[Rule 1 — Bug] Fixed Dexie liveQuery subscription type in App.vue**
- **Found during:** Task 3 typecheck
- **Issue:** `liveQuery().subscribe()` returns `Observable<unknown>` according to TypeScript, but actually returns a `Subscription` with `unsubscribe()`
- **Fix:** Used explicit `{ unsubscribe: () => void }` type annotation and cast
- **Files modified:** `frontend/src/App.vue`
- **Commit:** `e1c9244`

## Build Verification

```
vite build
✓ built in 5.72s

PWA v1.3.0
mode      injectManifest
format:   es
precache  99 entries (2668.03 KiB)
files generated
  dist/sw.js
```

- TypeScript: No new errors introduced (pre-existing errors unchanged)
- Vite build: ✅ Success
- PWA injection: ✅ 99 precached entries, SW built and injected

## Threat Surface

No new security surface introduced beyond what was modeled in the plan's threat register. All threat mitigations (T-13-06 through T-13-11) are implemented:
- T-13-06: 401 → SyncAuthError pause
- T-13-07: Server validation on all writes
- T-13-08: Auto-merge only on non-conflicting fields
- T-13-09: Break on network errors + isSyncing guard
- T-13-10: Same-origin protection
- T-13-11: User-confirmed SW update

## Known Stubs

None — all components have live data wired through the sync store and Dexie live subscriptions.

## Self-Check: PASSED

- [x] `frontend/src/services/sync.ts` — exists, compiles cleanly
- [x] `frontend/src/stores/sync.ts` — exists, compiles cleanly
- [x] `frontend/src/components/pwa/SyncIndicator.vue` — exists, compiles cleanly
- [x] `frontend/src/components/pwa/ConflictDialog.vue` — exists, compiles cleanly
- [x] `frontend/src/components/pwa/UpdatePrompt.vue` — exists, compiles cleanly
- [x] `frontend/src/main.ts` — modified, compiles cleanly
- [x] `frontend/src/App.vue` — modified, compiles cleanly
- [x] `frontend/src/modules/dashboard/pages/DashboardPage.vue` — modified, compiles cleanly
- [x] `frontend/src/env.d.ts` — modified, compiles cleanly
- [x] Commits exist: `9ba8824`, `d6a8a99`, `e1c9244`
- [x] Build succeeds with SW injection (99 entries, 2668 KiB)
