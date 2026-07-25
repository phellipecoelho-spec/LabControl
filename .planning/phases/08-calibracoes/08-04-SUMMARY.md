---
phase: 08-calibracoes
plan: 04
subsystem: frontend-ui
tags: [vue, primevue, calibration, frontend-ui, dialogs, tabs, datatable, timeline]

# Dependency graph
requires:
  - phase: 08-calibracoes
    plan: 03
    provides: Calibration types, service, store, routes, and navigation
provides:
  - CalibrationListPage with 4 filters (equipment select, status, date range, laboratory) and 8-column DataTable
  - CalibrationDetailPage with 3 tabs (Info, Certificates, Timeline) and action buttons
  - CalibrationCreateDialog with 8 fields and validation
  - CalibrationConcludeDialog with completion form pre-filled from calibration data
  - CalibrationInfoTab with 2-column grid, due/due-soon alerts, interval formatting
  - CalibrationCertificateTab with DataTable + file upload/download/delete with type validation
  - CalibrationTimelineTab with PrimeVue Timeline showing created/completed/cancelled events
affects: [frontend-ui]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Dialog pattern: Modal form with v-model:visible, handleSave/resetForm/saving guard, Toast on error"
    - "Tab pattern: TabList + TabPanels with 3 panels (Info, Certificates, Timeline)"
    - "Timeline pattern: Computed events array from model state (created/completed/cancelled)"
    - "List page pattern: DataTable with lazy pagination, filter toolbar, rowClass for due highlighting"

key-files:
  created:
    - frontend/src/modules/calibrations/pages/CalibrationListPage.vue
    - frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue
    - frontend/src/modules/calibrations/components/CalibrationInfoTab.vue
    - frontend/src/modules/calibrations/components/CalibrationCertificateTab.vue
    - frontend/src/modules/calibrations/components/CalibrationTimelineTab.vue
    - frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue
    - frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue
  modified: []

key-decisions:
  - "CalibrationCertificateTab uses hidden file input + Button instead of PrimeVue FileUpload for simpler UX (no drag-and-drop needed for certificates)"
  - "CalibrationTimelineTab uses computed events from calibration model state (not LogsActivity) — same pattern as LoanTimelineTab"
  - "All permission checks use authStore.hasPermission('calibracoes.*') pattern per D-21"

patterns-established:
  - "Calibration UI pattern: ListPage → CreateDialog/ConcludeDialog → DetailPage with 3 tabs"
  - "Due indicators: rowClass for overdue in list, Tag badges for due-soon, Message alerts in detail info tab"
  - "DatePicker v-model: Use `any` type for DatePicker-bound form fields to handle Date/string duality"

requirements-completed: [CAL-01, CAL-02, CAL-03, CAL-04]

coverage:
  - id: D1
    description: "CalibrationListPage with 4 filters and 8-column DataTable"
    requirement: CAL-01
    verification:
      - kind: unit
        ref: "vue-tsc --noEmit — no calibration-related errors"
        status: pass
    human_judgment: false
  - id: D2
    description: "CalibrationCreateDialog with 8 fields and form validation"
    requirement: CAL-01
    verification:
      - kind: unit
        ref: "frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue — required field validation guard"
        status: pass
    human_judgment: false
  - id: D3
    description: "CalibrationConcludeDialog with completion form, pre-fills from calibration"
    requirement: CAL-01
    verification:
      - kind: unit
        ref: "frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue — watch(calibration) to populate fields"
        status: pass
    human_judgment: false
  - id: D4
    description: "CalibrationDetailPage with 3 tabs (Info, Certificates, Timeline) and action buttons"
    requirement: CAL-01
    verification:
      - kind: unit
        ref: "frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue — 3 TabPanels, permission-gated Concluir/Cancelar"
        status: pass
    human_judgment: false
  - id: D5
    description: "CalibrationInfoTab with 2-column grid, status Tags, due indicators, interval formatting"
    requirement: CAL-04
    verification:
      - kind: unit
        ref: "frontend/src/modules/calibrations/components/CalibrationInfoTab.vue — 6 Card groups, due/due_soon Message alerts"
        status: pass
    human_judgment: false
  - id: D6
    description: "CalibrationCertificateTab with DataTable, upload/download/delete, type validation (PDF, JPG, PNG, WebP, max 10MB)"
    requirement: CAL-02
    verification:
      - kind: unit
        ref: "frontend/src/modules/calibrations/components/CalibrationCertificateTab.vue — 7 columns, file input with MIME/size validation"
        status: pass
    human_judgment: false
  - id: D7
    description: "CalibrationTimelineTab with PrimeVue Timeline showing 2-3 events based on status"
    requirement: CAL-04
    verification:
      - kind: unit
        ref: "frontend/src/modules/calibrations/components/CalibrationTimelineTab.vue — computed events for created/completed/cancelled"
        status: pass
    human_judgment: false

# Metrics
duration: 9min
completed: 2026-07-25
status: complete
---

# Phase 8: Calibrações — Plan 04 Summary

**7 Vue components for Calibrações UI — list page with 4 filters, detail page with 3 tabs, create/conclude dialogs, certificate tab with upload/download/delete, timeline tab with lifecycle events**

## Performance

- **Duration:** 9 min
- **Started:** 2026-07-25T14:53:27Z
- **Completed:** 2026-07-25T15:02:30Z
- **Tasks:** 3
- **Files modified:** 7

## Accomplishments

- **CalibrationListPage** — DataTable with 4 filters (equipment select, status, date range from/to, laboratory search), 8 columns (Equipamento, Parte, Data Agendada, Data Conclusão, Próxima Data, Laboratório, Status, Ações), lazy pagination, striped rows, overdue row highlighting (`.p-row-due`), due-soon Tags ("Vence em X dias"), permission-gated action buttons (view all, edit/delete conditional on status + `calibracoes.edit`), integrated Create and Conclude dialogs
- **CalibrationDetailPage** — Back button, status/due Tags, action buttons "Concluir" (gated by `calibracoes.concluir` when `scheduled`) and "Cancelar" (gated by `calibracoes.cancel` when `scheduled`), 3-tab layout (Info/Certificates/Timeline), Skeleton loading state, ConcludeDialog integration with success Toast
- **CalibrationCreateDialog** — Modal form with 8 fields: Equipment (Select with filter, fetched from store), Part Name (InputText, optional), Scheduled Date (DatePicker, required), Interval Value (InputNumber, min:1) + Interval Unit (Select: months/days/hours), Responsible (InputText), Laboratory (InputText), Notes (Textarea); validation guard for 4 required fields; emits 'saved' on success
- **CalibrationConcludeDialog** — Modal form with completion fields: Completed At (DatePicker, default today), Certificate Number (InputText), Responsible/Laboratory (pre-filled from calibration), Notes (Textarea); emits 'saved' on success
- **CalibrationInfoTab** — 6 Card groups in 2-column grid: equipment details, dates with due/due-soon Message alerts, interval display ("6 meses"), status Tag, responsible/laboratory, notes with expand
- **CalibrationCertificateTab** — DataTable with 7 columns (Nome do Arquivo, Tipo, Tamanho, Nº Certificado, Emissor, Data Emissão, Ações), hidden file input + Button for upload with client-side MIME validation (PDF/JPEG/PNG/WebP, max 10MB), download (opens in new tab from storage URL), delete with ConfirmDialog, permission-gated (Adicionar only with `calibracoes.edit`, Delete only with `calibracoes.edit`)
- **CalibrationTimelineTab** — PrimeVue Timeline with computed events: created (blue, pi-plus-circle), completed (green, pi-check-circle), cancelled (red, pi-times-circle); formatted date descriptions

## Task Commits

Each task was committed atomically:

1. **Task 1: Create CalibrationCreateDialog and CalibrationConcludeDialog** - `21d83cf` (feat)
2. **Task 2: Create CalibrationInfoTab, CalibrationCertificateTab, and CalibrationTimelineTab** - `3cdf3bc` (feat)
3. **Task 3: Create CalibrationListPage and CalibrationDetailPage** - `9de0d01` (feat)

**Fix commit:** `a09b0aa` (fix) — Fixed DatePicker v-model type compatibility in both dialogs

## Files Created

- `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` - Main list page with DataTable, filters, dialogs
- `frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue` - Detail page with 3 tabs and action buttons
- `frontend/src/modules/calibrations/components/CalibrationInfoTab.vue` - Calibration data display tab
- `frontend/src/modules/calibrations/components/CalibrationCertificateTab.vue` - Certificate list + upload/download/delete tab
- `frontend/src/modules/calibrations/components/CalibrationTimelineTab.vue` - Lifecycle timeline tab
- `frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue` - Create calibration modal dialog
- `frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue` - Complete calibration modal dialog

## Decisions Made

- **Certificate upload approach:** Used hidden file input + Button wrapper instead of PrimeVue FileUpload to keep the UI simpler — certificates don't need drag-and-drop like photos
- **Timeline data source:** Computed events from calibration model state (created_at, completed_at, status) rather than LogsActivity — same approach as LoanTimelineTab since the backend API returns these fields directly
- **DatePicker v-model typing:** Used `any` type for DatePicker-bound form fields to handle the Date/string duality cleanly, then converted to ISO string strings before API calls

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed DatePicker v-model TypeScript type incompatibility**
- **Found during:** Task 3 verification (vue-tsc)
- **Issue:** PrimeVue DatePicker v-model expects `Date` type but CalibrationFormData defines dates as `string`. This caused 6 type errors across CalibrationCreateDialog and CalibrationConcludeDialog.
- **Fix:** Changed form state types to use `any` for DatePicker-bound fields, then convert to ISO date string before constructing the typed payload for API calls.
- **Files modified:** CalibrationCreateDialog.vue, CalibrationConcludeDialog.vue
- **Verification:** vue-tsc --noEmit passes with zero calibration errors
- **Committed in:** a09b0aa (Task 3 commit, fix commit)

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** Necessary fix for type compatibility. Follows same pattern as existing LoanCreateDialog which has identical DatePicker typing.

## Issues Encountered

None.

## Self-Check: PASSED

- [x] All 7 created files verified on disk
- [x] 4 commits exist with proper formatting (3feat, 1fix)
- [x] `vue-tsc --noEmit` — zero calibration-related type errors
- [x] `vite build --logLevel error` — passes without errors
- [x] CalibrationListPage has 4 filters (equipment, status, date range, laboratory) + 8 DataTable columns
- [x] CalibrationDetailPage has 3 tabs (Info, Certificates, Timeline) + Concluir/Cancelar buttons
- [x] CalibrationCreateDialog has 8 fields with 4 required validations
- [x] CalibrationConcludeDialog has 5 fields with default today for completed_at
- [x] CalibrationCertificateTab validates file MIME types and size (PDF, JPG, PNG, WebP, max 10MB)
- [x] CalibrationTimelineTab shows events based on calibration status
- [x] Permission gates: create button (calibracoes.create), complete (calibracoes.concluir), cancel (calibracoes.cancel), edit/delete (calibracoes.edit)
- [x] Overdue calibrations have visual indicators (row highlight + due tags)

## Next Phase Readiness

- Phase 08 (Calibrações) frontend UI now complete — all 7 components created and type-checked
- Ready for Phase 09 (Aferições — Verificações) or any verification step
- All routes already registered in Plan 03 — pages are lazy-loaded and ready at `/calibrations` and `/calibrations/:id`

---

*Phase: 08-calibracoes*
*Completed: 2026-07-25*
