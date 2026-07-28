---
phase: 14-auditoria-ajustes
plan: 02
subsystem: ui
tags: [primevue, skeleton, dark-mode, responsive, empty-state, vue]
requires: []
provides:
  - EmptyState reusable component for empty list states
  - LoadingSkeleton reusable component with 4 variants
  - Consistent empty/loading states across all 13+ pages
  - Responsive scrollable DataTables on all list pages
  - Dark mode compatible form labels
affects: []

tech-stack:
  added: []
  patterns:
    - "EmptyState with icon/title/description/action for empty list states"
    - "LoadingSkeleton with 4 variants (table/card/form/detail)"
    - "Conditional template rendering: loading → skeleton → empty → content"

key-files:
  created:
    - frontend/src/components/EmptyState.vue
    - frontend/src/components/LoadingSkeleton.vue
    - frontend/src/styles/empty-state.css
  modified:
    - frontend/src/modules/equipment/pages/EquipmentListPage.vue
    - frontend/src/modules/equipment/pages/EquipmentDetailPage.vue
    - frontend/src/modules/equipment/pages/EquipmentFormPage.vue
    - frontend/src/modules/inventory/pages/InventoryItemListPage.vue
    - frontend/src/modules/inventory/pages/InventoryItemDetailPage.vue
    - frontend/src/modules/inventory/pages/InventoryItemFormPage.vue
    - frontend/src/modules/loans/pages/LoanListPage.vue
    - frontend/src/modules/loans/pages/LoanDetailPage.vue
    - frontend/src/modules/calibrations/pages/CalibrationListPage.vue
    - frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue
    - frontend/src/modules/verifications/pages/VerificationPendingPage.vue
    - frontend/src/modules/maintenance/pages/MaintenanceListPage.vue
    - frontend/src/modules/maintenance/pages/MaintenanceDetailPage.vue
    - frontend/src/modules/reports/pages/ReportPage.vue
    - frontend/src/modules/dashboard/pages/DashboardPage.vue

key-decisions:
  - "EmptyState emits @action event for non-route CTA actions (e.g., opening dialogs)"
  - "LoadingSkeleton uses PrimeVue Skeleton internally, not custom CSS animations"
  - "DashboardPage replaced ProgressSpinner with LoadingSkeleton card variant for consistency"

patterns-established:
  - "Page template pattern: LoadingSkeleton v-if=loading → EmptyState v-else-if=empty → Content v-else"
  - "Form labels use theme-aware text-color class instead of text-900 for dark mode compatibility"
  - "All list DataTables use scrollable + scrollHeight='flex' for responsive horizontal overflow"

requirements-completed: [LAYOUT-01, LAYOUT-02, LAYOUT-03]

coverage:
  - id: D1
    description: "EmptyState reusable component with icon/title/description/action props and slots"
    requirement: LAYOUT-01
    verification:
      - kind: other
        ref: "frontend/src/components/EmptyState.vue"
        status: pass
    human_judgment: false
  - id: D2
    description: "LoadingSkeleton reusable component with table/card/form/detail variants"
    requirement: LAYOUT-01
    verification:
      - kind: other
        ref: "frontend/src/components/LoadingSkeleton.vue"
        status: pass
    human_judgment: false
  - id: D3
    description: "All list pages show EmptyState when no data (Equipment, Inventory, Loans, Calibrations, Verifications, Maintenance, Reports)"
    requirement: LAYOUT-02
    verification: []
    human_judgment: true
    rationale: "Requires visual verification in browser with empty data — automation cannot detect rendered empty state"
  - id: D4
    description: "All pages show LoadingSkeleton during data loading"
    requirement: LAYOUT-02
    verification: []
    human_judgment: true
    rationale: "Requires visual verification with slow network simulation — automation cannot detect rendered skeleton"
  - id: D5
    description: "Detail pages show EmptyState when record not found with back-to-list CTA"
    requirement: LAYOUT-02
    verification: []
    human_judgment: true
    rationale: "Requires manual navigation to non-existent record — automation cannot trigger 404 display"
  - id: D6
    description: "Dark mode compatible form labels (text-color instead of text-900)"
    requirement: LAYOUT-03
    verification: []
    human_judgment: true
    rationale: "Requires visual verification in dark/light mode toggle"
  - id: D7
    description: "Responsive scrollable DataTables on all list pages"
    requirement: LAYOUT-03
    verification: []
    human_judgment: true
    rationale: "Requires viewport resize and visual inspection of horizontal scroll behavior"

duration: ~60min
completed: 2026-07-28
status: complete
---

# Phase 14 Plan 02: UI Polish Summary

**Reusable EmptyState and LoadingSkeleton components integrated into all 13+ pages, with dark mode form labels and responsive scrollable DataTables**

## Performance

- **Duration:** ~60 min
- **Started:** 2026-07-28T02:00:00Z
- **Completed:** 2026-07-28T06:55:00Z
- **Tasks:** 3
- **Files modified/created:** 19

## Accomplishments

- Created reusable EmptyState.vue component (icon/title/description/action props, emit event for non-route CTAs, actions slot)
- Created reusable LoadingSkeleton.vue component with 4 variants: table (DataTable simulation), card (card grid), form (form fields), detail (label-value rows)
- Integrated EmptyState into all 7 list pages with contextual icons, titles, descriptions, and CTAs
- Integrated LoadingSkeleton detail variant into all 5 detail pages for loading state
- Added EmptyState "not found" display to all 5 detail pages with back-to-list CTA
- Replaced inline/raw loading states with reusable LoadingSkeleton on DashboardPage and ReportPage
- Added loading skeleton to form pages (EquipmentFormPage, InventoryItemFormPage) for edit mode data fetch
- Added `scrollable scrollHeight="flex"` to all list DataTables for responsive horizontal overflow
- Replaced `text-900` with theme-aware `text-color` class in all form labels for dark mode compatibility
- Removed unused CSS classes (dashboard-loading, inline empty-state styles)
- Deferred pre-existing TypeScript errors to deferred-items.md

## Task Commits

1. **Task 1: Create reusable EmptyState and LoadingSkeleton components** - `eb7a72b` (feat)
2. **Task 2: Integrate EmptyState and LoadingSkeleton into all pages** - `75562a3` (feat)
3. **Task 3: Responsive & dark mode audit** - `62878e1` (fix)

**Plan metadata:** (created via final commit)

## Files Created/Modified

### Created
- `frontend/src/components/EmptyState.vue` — Reusable empty state component with icon, title, description, CTA, slots, and action emit
- `frontend/src/components/LoadingSkeleton.vue` — Reusable skeleton component with 4 variants using PrimeVue Skeleton
- `frontend/src/styles/empty-state.css` — Global CSS classes for empty state illustration and container

### Modified (list pages)
- `frontend/src/modules/equipment/pages/EquipmentListPage.vue` — Added LoadingSkeleton + EmptyState with "Novo Equipamento" CTA
- `frontend/src/modules/inventory/pages/InventoryItemListPage.vue` — Added LoadingSkeleton + EmptyState with "Novo Item" CTA
- `frontend/src/modules/loans/pages/LoanListPage.vue` — Added LoadingSkeleton + EmptyState (dialog via @action emit)
- `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` — Added LoadingSkeleton + EmptyState (dialog via @action emit)
- `frontend/src/modules/verifications/pages/VerificationPendingPage.vue` — Replaced inline skeleton/empty with components
- `frontend/src/modules/maintenance/pages/MaintenanceListPage.vue` — Added LoadingSkeleton + EmptyState (dialog @action), replaced template #empty
- `frontend/src/modules/reports/pages/ReportPage.vue` — Replaced inline skeleton/empty with LoadingSkeleton card + EmptyState

### Modified (detail pages)
- `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` — LoadingSkeleton detail + EmptyState "not found"
- `frontend/src/modules/inventory/pages/InventoryItemDetailPage.vue` — LoadingSkeleton detail + EmptyState "not found"
- `frontend/src/modules/loans/pages/LoanDetailPage.vue` — LoadingSkeleton detail + EmptyState, replaced inline "not found"
- `frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue` — LoadingSkeleton detail + EmptyState, replaced inline "not found"
- `frontend/src/modules/maintenance/pages/MaintenanceDetailPage.vue` — LoadingSkeleton detail + EmptyState "not found"

### Modified (other)
- `frontend/src/modules/dashboard/pages/DashboardPage.vue` — Replaced ProgressSpinner with LoadingSkeleton card, removed unused CSS
- `frontend/src/modules/equipment/pages/EquipmentFormPage.vue` — Added loading skeleton for edit mode, dark mode form labels
- `frontend/src/modules/inventory/pages/InventoryItemFormPage.vue` — Added loading skeleton for edit mode, dark mode form labels

## Decisions Made

- **EmptyState action emit:** Added `@action` emit to EmptyState.vue so parent components can handle non-route CTAs (e.g., opening dialogs for Loan, Calibration, Maintenance creation). This was needed because some create actions use dialogs, not route navigation.
- **LoadingSkeleton variants designed for PrimeVue:** The 4 variants (table/card/form/detail) match the actual page layouts in the app, using PrimeVue Skeleton component as the base.
- **Dashboard loading**: Replaced the module's standalone ProgressSpinner with LoadingSkeleton card variant for visual consistency with the rest of the app.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Added `@action` emit to EmptyState**
- **Found during:** Task 2 (LoanListPage integration)
- **Issue:** Plan assumed all CTAs use routes, but Loan, Calibration, and Maintenance CTAs open dialogs, not route navigations
- **Fix:** Added `defineEmits<{ action: [] }>()` to EmptyState. When `actionRoute` is not provided, the button emits `@action` instead of calling `router.push()`
- **Files modified:** frontend/src/components/EmptyState.vue
- **Verification:** Build passes, TS types correct
- **Committed in:** 75562a3 (Task 2 commit)

**2. [Rule 2 - Missing Critical] Added loading skeleton to form pages**
- **Found during:** Task 3 (form page audit)
- **Issue:** EquipmentFormPage and InventoryItemFormPage had no loading state during edit mode data fetch
- **Fix:** Added `loading` ref with LoadingSkeleton variant="form" shown during initial edit data load
- **Files modified:** frontend/src/modules/equipment/pages/EquipmentFormPage.vue, frontend/src/modules/inventory/pages/InventoryItemFormPage.vue
- **Verification:** Build passes
- **Committed in:** 62878e1 (Task 3 commit)

---

**Total deviations:** 2 auto-fixed (2 missing critical)
**Impact on plan:** Both auto-fixes necessary for complete UX polish. No scope creep.

## Issues Encountered

- Pre-existing TypeScript errors in 4 unrelated files (PasswordInput.vue, EquipmentLogsSection.vue, LoanCreateDialog.vue, router/index.ts) were documented in deferred-items.md. These are not caused by this plan.
- The `EmptyState` in the dashboard module was intentionally NOT modified per plan instructions — the new EmptyState component lives in `frontend/src/components/` for global reuse.

## User Setup Required

None — no external service configuration required. Run `npm run dev` to verify in browser.

## Next Phase Readiness

- All list and detail pages have consistent loading/empty/found states
- UI framework for empty and loading states is established with reusable components
- Dark mode form labels and responsive DataTables in place
- Next plan: 14-03 (Form validation standardization, component testing) can build on these reusable components

---
*Phase: 14-auditoria-ajustes*
*Completed: 2026-07-28*

## Self-Check: PASSED

- ✅ frontend/src/components/EmptyState.vue — FOUND
- ✅ frontend/src/components/LoadingSkeleton.vue — FOUND
- ✅ frontend/src/styles/empty-state.css — FOUND
- ✅ 14-02-SUMMARY.md — FOUND
- ✅ eb7a72b — Task 1 commit
- ✅ 75562a3 — Task 2 commit
- ✅ 62878e1 — Task 3 commit