---
phase: 07-emprestimos
plan: 04
subsystem: loans
tags:
  - ui
  - frontend
  - loans
  - datatable
  - dialog
  - primevue
dependency_graph:
  requires:
    - 07-03 (Loan types, store, service, routes)
  provides:
    - LoanListPage with DataTable + filters
    - LoanCreateDialog (modal creation form)
    - LoanDetailPage with 3 tabs (Dados, Itens, Timeline)
    - LoanReturnDialog (partial return)
  affects:
    - frontend router (loans.index, loans.show)
    - navigation sidebar (loan module)
tech-stack:
  added:
    - PrimeVue Timeline component
    - PrimeVue ProgressBar for loan progress
    - PrimeVue DatePicker for date range filters
    - PrimeVue MultiSelect for equipment filter
  patterns:
    - ListPage + DetailPage + Dialog (D-10)
    - Permission-gated buttons (emprestimos.*)
    - Lazy pagination with filter state
    - Status Tag severity mapping
key-files:
  created:
    - frontend/src/modules/loans/components/LoanCreateDialog.vue
    - frontend/src/modules/loans/components/LoanInfoTab.vue
    - frontend/src/modules/loans/components/LoanItemsTab.vue
    - frontend/src/modules/loans/components/LoanTimelineTab.vue
    - frontend/src/modules/loans/components/LoanReturnDialog.vue
  modified:
    - frontend/src/modules/loans/pages/LoanListPage.vue (from stub)
    - frontend/src/modules/loans/pages/LoanDetailPage.vue (from stub)
decisions:
  - "Equipment filter in LoanListPage uses MultiSelect with label 'name - patrimony_id' per D-11"
  - "Timeline built from loan data fields (no activity log dependency) — shows placeholder when empty"
  - "Return dialog makes sequential API calls per item (one at a time) for reliability"
  - "Action buttons in DetailPage gated by permission AND loan status (e.g. activate only for reserved)"
metrics:
  duration: 15min
  completed_date: 2026-07-21
  tasks_completed: 2
  files_created: 5
  files_modified: 2
  commits: 2
status: complete
---

# Phase 7 Plan 4: UI Components for Empréstimos (Loan) Module — Summary

**One-liner:** LoanListPage with paginated DataTable + MultiSelect equipment filter (D-11), LoanCreateDialog with borrower/equipment MultiSelect, LoanDetailPage with 3-tab layout (Dados/Itens/Timeline) and LoanReturnDialog for partial returns — all permission-gated and following existing module patterns.

## Completed Tasks

### Task 1: LoanListPage + LoanCreateDialog

**Files created/modified:**
- `LoanListPage.vue` — Full replace from stub. DataTable with columns per D-07 (Tomador, Equipamentos Tags, Data Retirada, Data Prevista, Status Tag, Ações), lazy pagination, search-by-borrower with debounce, status Select, equipment MultiSelect (populated via `store.fetchEquipment({ all: true })` per D-11), date range filters (from/to DatePicker). Overdue highlighting via `p-row-overdue` CSS class. Permission-gated "Novo Empréstimo" button. Emergency action buttons (eye, edit, delete) with permission checks.
- `LoanCreateDialog.vue` — Modal dialog with borrower searchable Select, equipment MultiSelect (label `name - patrimony_id`), DatePickers for borrowed_at and expected_return_at, optional fields (reason, destination, contact, notes, approved_by). Client-side validation on required fields (borrower, equipment, dates). Stores calls `store.create()` and emits `saved` on success. Toast feedback for errors.

### Task 2: LoanDetailPage + 3 Tab Components + LoanReturnDialog

**Files created/modified:**
- `LoanDetailPage.vue` — Full replace from stub. Header with back button, loan ID, status Tag (with overdue indicator). Action buttons: "Ativar" (reserved + emprestimos.edit), "Cancelar" (reserved + emprestimos.finalizar), "Devolver Itens" (active + emprestimos.edit). ProgressBar showing returned/total items. PrimeVue Tabs with 3 panels (D-12).
- `LoanInfoTab.vue` — Read-only grid with Cards: borrower info, period (borrowed_at → expected_return_at → returned_at), destination, reason, approver, created_by, progress (ProgressBar), status Tag. Overdue alert via Message component with red styling.
- `LoanItemsTab.vue` — Simple DataTable (no pagination) of equipment items with columns: Equipamento, Patrimônio, Nº Série, Status (Tag verde/amarela), Devolvido Em, Observações. "Devolver" button per unreturned item.
- `LoanTimelineTab.vue` — PrimeVue Timeline component with events built from loan data: creation, reservation, activation, per-item returns, completion, cancellation. Color-coded icons per event type. Falls back to "Histórico disponível em breve" when no events.
- `LoanReturnDialog.vue` — Modal for partial returns. Lists only unreturned items with Checkbox selection. Each selected item shows DatePicker (default now) and notes InputText. Sequential API calls via `store.returnItem()`. Emits `returned` on success. Toast error handling.

## Verification Results

1. ✅ `npx vite build` — Build produced 0 errors, all chunks generated successfully
2. ✅ `LoanListPage-CDb9oGnL.js` — 13.63 kB chunk generated
3. ✅ `LoanDetailPage-C5wqzu3g.js` — 22.85 kB chunk generated
4. ✅ All 7 expected files exist on disk
5. ✅ All 2 commits present in git history

## Deviations from Plan

**None** — Plan executed exactly as written.

## Known Stubs

None identified. All components have full implementations with proper data wiring.

## Threat Flags

None. All components consume existing data through the LoanStore and do not introduce new endpoints or authentication paths.

## Self-Check: PASSED

- [x] All 7 files exist
- [x] `npx vite build` succeeds
- [x] 2 commits with proper format
