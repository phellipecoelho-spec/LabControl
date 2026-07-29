# Phase 13: PWA e Offline — Validation

**Phase:** 13-pwa-offline
**Generated:** 2026-07-27

## Validation Scope

This document defines the validation strategy for Phase 13 (PWA e Offline) covering both requirements PWA-01 (offline sync) and PWA-02 (installable app).

## Test Infrastructure

| Item | Location | Status |
|------|----------|--------|
| Unit tests | `frontend/src/db/__tests__/`, `frontend/src/stores/__tests__/`, `frontend/src/services/__tests__/` | To be created in Wave 0 |
| E2E tests | `frontend/tests-e2e/pwa.spec.ts` | To be created |
| Framework | Vitest (unit), Playwright (E2E) | To be configured |
| Lighthouse | Chrome DevTools Lighthouse PWA audit | Manual |

## Validation Scenarios

### Scenario 1: Service Worker Registration
- **Given** the app is loaded in a supported browser
- **When** the page finishes loading
- **Then** a service worker is registered for the origin
- **Type:** Automated unit test
- **Command:** Verify in DevTools → Application → Service Workers

### Scenario 2: Static Asset Caching
- **Given** the service worker is active
- **When** the app requests JS, CSS, or font files
- **Then** they are cached via Cache API and served from cache on repeated requests
- **Type:** Automated E2E (Playwright)
- **Command:** Offline mode → verify assets load

### Scenario 3: API GET Caching
- **Given** the service worker is active
- **When** a GET /api/v1/* request succeeds
- **Then** the response is stored in IndexedDB via Dexie
- **Type:** Automated unit test
- **Command:** `npx vitest run src/db/__tests__/entities.test.ts`

### Scenario 4: Offline Queue
- **Given** the browser is offline
- **When** the user creates or edits a record
- **Then** the operation is saved to the syncQueue IndexedDB table
- **And** a visual indicator shows N pending operations
- **Type:** Automated unit + manual check
- **Command:** `npx vitest run src/db/__tests__/syncQueue.test.ts`

### Scenario 5: Automatic Sync on Reconnect
- **Given** the browser was offline and has pending operations
- **When** connectivity is restored
- **Then** the pending operations are replayed automatically
- **And** the indicator disappears when all are synced
- **Type:** Automated integration
- **Command:** `npx vitest run src/services/__tests__/sync.test.ts`

### Scenario 6: Manual Sync
- **Given** the browser is online with pending operations
- **When** the user clicks "Sincronizar"
- **Then** all pending operations are replayed
- **Type:** Automated integration

### Scenario 7: Conflict Detection
- **Given** a record was edited offline and also changed on the server
- **When** the sync replays the operation
- **Then** a 409 Conflict is detected
- **And** a conflict record is stored with field-level diff
- **Type:** Automated integration

### Scenario 8: Conflict Resolution UI
- **Given** a conflict exists
- **When** the sync finishes
- **Then** a dialog shows the field-by-field diff
- **And** the user can choose to keep local, keep server, or merge
- **Type:** Manual verification

### Scenario 9: PWA Manifest
- **Given** the app is built for production
- **When** the manifest.json is loaded
- **Then** it contains name "LabControl", display "standalone", theme "#0f172a"
- **Type:** Automated unit
- **Command:** Verify manifest properties

### Scenario 10: Offline Full Experience
- **Given** the user has previously visited pages online
- **When** the browser goes offline
- **Then** the user can still navigate and view cached data
- **And** the Dashboard shows last synced timestamp
- **Type:** Manual (Lighthouse PWA audit)

## Validation Matrix

| Req ID | Scenario | Type | Priority | Automated |
|--------|----------|------|----------|-----------|
| PWA-02 | 1: SW Registration | Unit | Must | Yes |
| PWA-02 | 2: Asset Caching | E2E | Must | Yes |
| PWA-01 | 3: API GET Caching | Unit | Must | Yes |
| PWA-01 | 4: Offline Queue | Unit | Must | Yes |
| PWA-01 | 5: Auto Sync | Integration | Must | Yes |
| PWA-01 | 6: Manual Sync | Integration | Should | Yes |
| PWA-01 | 7: Conflict Detection | Integration | Should | Yes |
| PWA-01 | 8: Conflict UI | Manual | Must | No |
| PWA-02 | 9: Manifest | Unit | Must | Yes |
| PWA-01 | 10: Full Offline | Manual | Should | No |

## Acceptance Criteria

All Must-priority tests pass before Phase 13 is considered complete.

## Tools

- **Unit/Integration:** Vitest
- **E2E:** Playwright (for service worker and offline tests)
- **PWA Audit:** Chrome Lighthouse
- **Type Checking:** `vue-tsc --noEmit`
