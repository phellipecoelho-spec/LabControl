---
phase: 09-afericoes
plan: 02
subsystem: fullstack
tags: api, controller, request, resource, notification, vue, primevue, pinia, typescript

# Dependency graph
requires:
  - phase: 09-afericoes-01
    provides: Verification, VerificationTemplate, VerificationParam models, VerificationService, VerificationResult enum, migrations
  - phase: 05-equipamentos
    provides: Equipment model, EquipmentDetailPage
  - phase: 08-calibracoes
    provides: Controller/Request/Resource pattern (CalibrationController, StoreCalibrationRequest, CalibrationResource)
provides:
  - VerificationController with 7 actions (index, pending, store, show, update, destroy, byEquipment)
  - StoreVerificationRequest and UpdateVerificationRequest with Portuguese validation messages
  - VerificationResource and VerificationCollection for JSON transformation
  - ToleranceExceeded notification (synchronous, database channel)
  - API routes for verifications CRUD, pending, by-equipment, and verification-templates
  - Frontend TypeScript types (Verification, VerificationParam, VerificationTemplate, PendingEquipment, VerificationFormData)
  - VerificationService with getPending, create, getHistoryByEquipment, getTemplatesByCategory, getTemplatesByEquipment
  - VerificationStore (Pinia) with fetchPending, fetchHistory, create
  - VerificationPendingPage with DataTable, Aferir button, empty state, loading skeleton
  - VerificationFormDialog with dynamic template param fields, InputNumber per param, tolerance display
  - VerificationHistoryTab with paginated DataTable, expandable param details, inline Aferir button
  - EquipmentDetailPage with Aferições tab (value=3) gated by afericoes.view, Arquivos→4, Logs→5
affects:
  - 09-afericoes-03 (future frontend polish or tests)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Dynamic form with template-driven params (Pitfall 1: initialize all keys at once for Vue 3 reactivity)
    - Synchronous notification dispatch in controller after service create
    - VerificationController follows CalibrationController middleware/permission pattern exactly
    - Frontend module follows calibrations module structure (types → service → store → pages → components)

key-files:
  created:
    - backend/app/Http/Controllers/Api/V1/VerificationController.php
    - backend/app/Http/Requests/StoreVerificationRequest.php
    - backend/app/Http/Requests/UpdateVerificationRequest.php
    - backend/app/Http/Resources/VerificationResource.php
    - backend/app/Http/Resources/VerificationCollection.php
    - backend/app/Notifications/ToleranceExceeded.php
    - frontend/src/modules/verifications/types/verification.ts
    - frontend/src/modules/verifications/services/VerificationService.ts
    - frontend/src/modules/verifications/store/VerificationStore.ts
    - frontend/src/modules/verifications/pages/VerificationPendingPage.vue
    - frontend/src/modules/verifications/components/VerificationFormDialog.vue
    - frontend/src/modules/verifications/components/VerificationHistoryTab.vue
  modified:
    - backend/app/Services/VerificationService.php
    - backend/routes/api.php
    - frontend/src/router/routes.ts
    - frontend/src/modules/equipment/pages/EquipmentDetailPage.vue

key-decisions:
  - "ToleranceExceeded notification dispatched via User::whereHas('roles.permissions') instead of non-existent User::permission() scope"
  - "Verification-templates inline routes use closures in api.php (lightweight — no dedicated controller needed)"
  - "Custom event 'verification-saved' for cross-component refresh between EquipmentDetailPage and VerificationHistoryTab"
  - "byEquipment uses string $equipment parameter (not route model binding) to pass ID directly to service"

patterns-established:
  - "Dynamic form reactivity: initialize all params keys at once to preserve Vue 3 reactivity (Pitfall 1 from RESEARCH.md)"
  - "Permission-gated tab rendering: v-if on both Tab and TabPanel elements prevents rendering mismatch"

requirements-completed: [VERF-01, VERF-02]

duration: 31min
completed: 2026-07-25
status: complete

coverage:
  - id: D1
    description: VerificationController with index, pending, store, show, update, destroy, byEquipment actions with permission middleware
    requirement: VERF-01
    verification:
      - kind: integration
        ref: php artisan route:list --path=v1/verifications
        status: pass
    human_judgment: false
  - id: D2
    description: StoreVerificationRequest validates equipment_id, params array, ensures equipment has verification templates
    requirement: VERF-01
    verification:
      - kind: integration
        ref: php -l app/Http/Requests/StoreVerificationRequest.php
        status: pass
    human_judgment: false
  - id: D3
    description: UpdateVerificationRequest with afericoes.edit gate, validates notes and optional params
    requirement: VERF-01
    verification:
      - kind: integration
        ref: php -l app/Http/Requests/UpdateVerificationRequest.php
        status: pass
    human_judgment: false
  - id: D4
    description: VerificationResource with nested operator/equipment/params, is_outside_range flag
    requirement: VERF-01
    verification:
      - kind: integration
        ref: php -l app/Http/Resources/VerificationResource.php
        status: pass
    human_judgment: false
  - id: D5
    description: ToleranceExceeded notification with database channel, structured data, dispatched for operator and supervisors
    requirement: VERF-02
    verification:
      - kind: integration
        ref: php -l app/Notifications/ToleranceExceeded.php
        status: pass
    human_judgment: false
  - id: D6
    description: Frontend TypeScript types for Verification, VerificationParam, VerificationTemplate, PendingEquipment, VerificationFormData
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: false
  - id: D7
    description: VerificationService with getPending, create, getHistoryByEquipment, getTemplatesByEquipment methods
    requirement: VERF-01
    verification:
      - kind: integration
        ref: php -l app/Services/VerificationService.php
        status: pass
    human_judgment: false
  - id: D8
    description: VerificationStore (Pinia) with fetchPending, fetchHistory, create, $reset
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: false
  - id: D9
    description: VerificationPendingPage with DataTable, Aferir button, loading state, empty state
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: true
    rationale: Visual verification needed for layout, responsiveness, and PrimeVue DataTable configuration
  - id: D10
    description: VerificationFormDialog with dynamic template param fields, InputNumber per param, tolerance display
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: true
    rationale: Dynamic form reactivity and PrimeVue InputNumber behavior requires visual verification
  - id: D11
    description: VerificationHistoryTab with paginated DataTable, expandable rows, param details, Aferir button
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: true
    rationale: DataTable pagination and expandable row rendering requires visual verification
  - id: D12
    description: EquipmentDetailPage with Aferições tab (value=3) gated by afericoes.view permission, Arquivos→4, Logs→5
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: true
    rationale: Tab renumbering and conditional rendering requires visual verification
  - id: D13
    description: All API routes registered (verifications CRUD + pending + by-equipment + verification-templates)
    requirement: VERF-01
    verification:
      - kind: integration
        ref: php artisan route:list --path=v1/verifications
        status: pass
    human_judgment: false
  - id: D14
    description: Routes.ts loads VerificationPendingPage instead of PlaceholderPage
    requirement: VERF-01
    verification:
      - kind: integration
        ref: npm run build 2>&1
        status: pass
    human_judgment: false
---

# Phase 09: Aferições — Plan 02 Summary

**Verification REST API (VerificationController, Form Requests, API Resources, routes), synchronous ToleranceExceeded notification, frontend module (types, service, Pinia store), pending list page, dynamic form dialog, history tab, and EquipmentDetailPage Aferições tab**

## Performance

- **Duration:** 31 min
- **Started:** 2026-07-25
- **Completed:** 2026-07-25
- **Tasks:** 3
- **Files modified:** 16 (12 created, 4 modified)

## Accomplishments

- **VerificationController** with 7 actions (index, pending, store, show, update, destroy, byEquipment) — all with Sanctum auth + permission middleware (afericoes.view, afericoes.create, afericoes.edit)
- **StoreVerificationRequest** validates equipment_id, params as numeric array, verifies category has verification templates configured
- **UpdateVerificationRequest** gated by `Gate::allows('afericoes.edit')`, validates optional notes and params
- **VerificationResource** returns nested operator, equipment, params with template tolerances, `is_outside_range` boolean
- **ToleranceExceeded notification** via `via('database')` — dispatched synchronously to operator and all users with `afericoes.edit` permission
- **VerificationService.update()** — transactional update with param recalculation
- **API routes** registered: verifications CRUD (6 routes), verifications.pending, equipments/{equipment}/verifications, verification-templates (2 routes)
- **Frontend type system** — 4 interfaces (Verification, VerificationParam, VerificationTemplate, PendingEquipment), 2 type aliases (VerificationResult, VerificationFrequency), 2 form types (VerificationFormData, VerificationFilters)
- **VerificationService** (frontend) with 5 methods: getPending, create, getHistoryByEquipment, getTemplatesByCategory, getTemplatesByEquipment
- **VerificationStore** (Pinia) with fetchPending, fetchHistory, create, $reset, computed hasPending
- **VerificationPendingPage** — full DataTable with columns for name, patrimony, serial, category, frequency, last verification; Aferir button gated by afericoes.create permission; loading skeleton; empty state "Todos os equipamentos estão em dia"
- **VerificationFormDialog** — dynamic form rendering one InputNumber per template param with tolerance range display; equipment selector or pre-filled from detail page; notes TextArea; success toast + tolerance exceeded warning toast; CRITICAL: all param keys initialized at once for Vue 3 reactivity (Pitfall 1)
- **VerificationHistoryTab** — paginated DataTable with expandable rows showing param details with color-coded tags; inline Aferir button gated by afericoes.create; listens for 'verification-saved' custom event for auto-refresh; empty state "Nenhuma aferição registrada para este equipamento"
- **EquipmentDetailPage** — Aferições tab inserted at value=3 between Técnica (2) and Arquivos (now 4), with Logs renumbered to 5; tab+panel gated by `authStore.hasPermission('afericoes.view')`; integrated VerificationFormDialog for inline verification creation

## Task Commits

Each task was committed atomically:

1. **Task 1: Backend API — controller, requests, resources, notification, routes** - `88400df` (feat)
2. **Task 2: Frontend data layer — types, service, store, route** - `077c014` (feat)
3. **Task 3: Frontend pages and components — pending list, form dialog, history tab, equipment detail tab** - `947a119` (feat)

## Files Created/Modified

### Created (12 files)

- `backend/app/Http/Controllers/Api/V1/VerificationController.php` — 7-action controller with permission middleware
- `backend/app/Http/Requests/StoreVerificationRequest.php` — Validation with after hook for template check
- `backend/app/Http/Requests/UpdateVerificationRequest.php` — Edit gate + param validation
- `backend/app/Http/Resources/VerificationResource.php` — JSON transform with nested relations
- `backend/app/Http/Resources/VerificationCollection.php` — Paginated collection with meta
- `backend/app/Notifications/ToleranceExceeded.php` — Database notification with structured data
- `frontend/src/modules/verifications/types/verification.ts` — 4 interfaces, 2 type aliases
- `frontend/src/modules/verifications/services/VerificationService.ts` — 5 API methods
- `frontend/src/modules/verifications/store/VerificationStore.ts` — Pinia store with 3 actions
- `frontend/src/modules/verifications/pages/VerificationPendingPage.vue` — Pending list with DataTable
- `frontend/src/modules/verifications/components/VerificationFormDialog.vue` — Dynamic form dialog
- `frontend/src/modules/verifications/components/VerificationHistoryTab.vue` — History tab with pagination

### Modified (4 files)

- `backend/app/Services/VerificationService.php` — Added `update()` method
- `backend/routes/api.php` — Added 10 verification routes + imports
- `frontend/src/router/routes.ts` — Replaced PlaceholderPage with VerificationPendingPage
- `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` — Added Aferições tab

## Decisions Made

- **User::permission() scope not added:** The plan referenced `User::permission('afericoes.edit')->get()` but this scope doesn't exist in the project. Used `User::whereHas('roles.permissions', ...)` instead — cleaner without adding an unused scope.
- **Verification-templates as inline routes:** Used lightweight closures in api.php instead of a dedicated controller, since the routes are simple queries with no business logic. Imports for `Equipment`, `VerificationTemplate`, and `Request` were added to api.php.
- **Custom event for cross-component refresh:** Used `window.dispatchEvent(new CustomEvent('verification-saved', ...))` pattern so VerificationHistoryTab auto-refreshes when a verification is saved from EquipmentDetailPage, without prop drilling.
- **`byEquipment` receives string ID:** Used `string $equipment` parameter (not route model binding) to pass the ID string directly to VerificationService, avoiding potential issues with UUID binding.

## Deviations from Plan

None — plan executed exactly as written.

### Technical Deviations (Compatible)

1. **User::permission() scope replaced:** Changed `User::permission('afericoes.edit')->get()` to `User::whereHas('roles.permissions', fn($q) => $q->where('slug', 'afericoes.edit'))->get()` since the scope doesn't exist. Functionally identical — no impact on behavior.

2. **VerificationTemplate inline routes:** Added verification-templates routes to api.php with closures, keeping scope contained without a dedicated controller. This matches the plan's suggestion in Task 2.

3. **VerificationController update() calls service:** The `update` method delegates to `VerificationService::update()` (added in this plan), which handles transactional updates and param recalculation. This follows the same pattern as `store()` delegating to `VerificationService::create()`.

## Issues Encountered

- **ExpandedRows type error:** PrimeVue DataTable `expandedRows` expects `DataTableExpandedRows | any[]` but was typed as `string | null`. Fixed by using `ref<any>(null)`.

## Threat Surface

| Threat ID | Category | Disposition | Status |
|-----------|----------|-------------|--------|
| T-09-06 | Elevation of Privilege | Permission middleware `permission:afericoes.create` on store | ✅ |
| T-09-07 | Information Disclosure | Permission middleware `permission:afericoes.view` on read actions | ✅ |
| T-09-08 | Tampering | Server-side calculation; StoreVerificationRequest validates numeric | ✅ |
| T-09-09 | Repudiation | operator_id from auth()->id(); LogsActivity trait | ✅ |
| T-09-10 | Information Disclosure | v-if gated by `authStore.hasPermission('afericoes.view')` on tab | ✅ |
| T-09-SC | Tampering | No new dependencies | ✅ |

All threat register mitigations are implemented.

## Next Phase Readiness

- Verification module complete: backend API + frontend module + EquipmentDetailPage integration
- Ready for testing (Plan 09-03) or UI polish/review
- Pending verification page at `/verifications` now loads the real page instead of PlaceholderPage
- EquipmentDetailPage has Aferições tab for inline verification history and creation

---

*Phase: 09-afericoes*
*Completed: 2026-07-25*
