---
phase: 08
name: calibracoes
status: passed
verified_by: opencode
verified_date: 2026-07-28
plan_count: 4
plan_complete: 4
plan_failed: 0
overall: pass
---

# Phase 08 — Calibrações: Cross-Plan Verification

**Date:** 2026-07-28
**Verification type:** Artifact presence + must_haves audit
**Result:** PASSED

---

## Plan 08-01 — Data Layer (Backend)

**Requirements:** CAL-01, CAL-02, CAL-04

| must_have | Status | Detail |
|-----------|--------|--------|
| Calibrations belong to one equipment (FK equipment_id) | PASS | Migration defines `foreignUuid('equipment_id')->constrained('equipments')` |
| Calibration statuses: scheduled → completed/cancelled (terminal) | PASS | CalibrationStatus enum with `canTransitionTo()` guard |
| Periodicidade modeled as interval_value + interval_unit | PASS | Migration columns `interval_value` (int) + `interval_unit` (string) |
| next_due_at computed as completed_at + interval | PASS | CalibrationService::calculateNextDue() uses Carbon addMonths/addDays/addHours |
| Certificates stored as 1:N via calibration_certificates table | PASS | Separate table with FK to calibrations, cascade delete |
| Files stored at storage/app/public/calibrations/certificates/ | PASS | CalibrationCertificateService uploads to disk `public` at that path |

**Artifact verification:**

| Artifact | Path | Exists |
|----------|------|--------|
| Compound migration | `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php` | ✅ |
| CalibrationStatus enum | `backend/app/Enums/CalibrationStatus.php` | ✅ |
| Calibration model | `backend/app/Models/Calibration.php` | ✅ |
| CalibrationCertificate model | `backend/app/Models/CalibrationCertificate.php` | ✅ |
| CalibrationException | `backend/app/Exceptions/CalibrationException.php` | ✅ |
| CalibrationService | `backend/app/Services/CalibrationService.php` | ✅ |
| CalibrationCertificateService | `backend/app/Services/CalibrationCertificateService.php` | ✅ |
| CalibrationFactory | `backend/database/factories/CalibrationFactory.php` | ✅ |
| CalibrationSeeder | `backend/database/seeders/CalibrationSeeder.php` | ✅ |
| RolePermissionSeeder (modified) | `backend/database/seeders/RolePermissionSeeder.php` | ✅ |
| DatabaseSeeder (modified) | `backend/database/seeders/DatabaseSeeder.php` | ✅ |

**Plan 08-01: PASSED** (11/11 artifacts, 6/6 truths)

---

## Plan 08-02 — API Layer (Backend)

**Requirements:** CAL-01, CAL-02, CAL-03, CAL-04

| must_have | Status | Detail |
|-----------|--------|--------|
| CRUD via REST API with filters | PASS | CalibrationController index filters equipment, status, date range, laboratory |
| Certificates listed/uploaded/downloaded/deleted per calibration | PASS | CalibrationCertificateController with 4 actions |
| Status transitions via dedicated endpoints | PASS | POST /calibrations/{id}/complete and /cancel |
| Validation rules enforce data integrity | PASS | 3 Form Requests (Store, Update, Complete) |
| Consistent API resource format | PASS | CalibrationResource + CalibrationCollection |
| CheckCalibrationDue command finds due calibrations (30 days) | PASS | Command creates in-app notifications for admin/supervisor |
| CalibrationDue marker class exists | PASS | Notification class for polymorphic type resolution |
| Command registered in AppServiceProvider as daily task | PASS | `$schedule->command('calibrations:check-due')->daily()` |

**Artifact verification:**

| Artifact | Path | Exists |
|----------|------|--------|
| CalibrationController | `backend/app/Http/Controllers/Api/V1/CalibrationController.php` | ✅ |
| CalibrationCertificateController | `backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php` | ✅ |
| StoreCalibrationRequest | `backend/app/Http/Requests/StoreCalibrationRequest.php` | ✅ |
| UpdateCalibrationRequest | `backend/app/Http/Requests/UpdateCalibrationRequest.php` | ✅ |
| CompleteCalibrationRequest | `backend/app/Http/Requests/CompleteCalibrationRequest.php` | ✅ |
| CalibrationResource | `backend/app/Http/Resources/CalibrationResource.php` | ✅ |
| CalibrationCollection | `backend/app/Http/Resources/CalibrationCollection.php` | ✅ |
| CheckCalibrationDue command | `backend/app/Console/Commands/CheckCalibrationDue.php` | ✅ |
| CalibrationDue notification | `backend/app/Notifications/CalibrationDue.php` | ✅ |
| routes/api.php (modified) | `backend/routes/api.php` | ✅ |
| AppServiceProvider (modified) | `backend/app/Providers/AppServiceProvider.php` | ✅ |

**Plan 08-02: PASSED** (11/11 artifacts, 8/8 truths)

---

## Plan 08-03 — Frontend Data Layer

**Requirements:** CAL-01, CAL-02, CAL-04

| must_have | Status | Detail |
|-----------|--------|--------|
| TypeScript interfaces for Calibration, Certificate, form data | PASS | calibration.ts defines all interfaces + status/interval constants |
| CalibrationService with methods for all 9+ API endpoints | PASS | 11 methods (CRUD + complete/cancel + equipment + certificates) |
| CalibrationStore (Pinia) manages state | PASS | Composition API store with pagination, loading, equipment options |
| Routes /calibrations and /calibrations/:id replace placeholder | PASS | routes.ts lazy-loads CalibrationListPage + CalibrationDetailPage |
| navigation.ts icon updated to pi-verified | PASS | D-22 satisfied |

**Artifact verification:**

| Artifact | Path | Exists |
|----------|------|--------|
| TypeScript types | `frontend/src/modules/calibrations/types/calibration.ts` | ✅ |
| CalibrationService | `frontend/src/modules/calibrations/services/CalibrationService.ts` | ✅ |
| CalibrationStore | `frontend/src/modules/calibrations/store/CalibrationStore.ts` | ✅ |
| routes.ts (modified) | `frontend/src/router/routes.ts` | ✅ |
| navigation.ts (modified) | `frontend/src/types/navigation.ts` | ✅ |

**Plan 08-03: PASSED** (5/5 artifacts, 5/5 truths)

---

## Plan 08-04 — Frontend UI

**Requirements:** CAL-01, CAL-02, CAL-03, CAL-04

| must_have | Status | Detail |
|-----------|--------|--------|
| List calibrations with filters (equipment/status/date/laboratory) | PASS | 4 filters in toolbar + DataTable with lazy pagination |
| Create calibrations via modal dialog | PASS | CalibrationCreateDialog with 8 fields and 4 required validations |
| View detail with 3 tabs (Info/Certificates/Timeline) | PASS | CalibrationDetailPage with TabPanels |
| Conclude calibration via dialog | PASS | CalibrationConcludeDialog with pre-filled fields |
| Cancel calibration with confirmation | PASS | ConfirmDialog before store.cancel() |
| View/upload/download certificates | PASS | CalibrationCertificateTab with file input + DataTable |
| Overdue/due-soon visual indicators | PASS | rowClass highlight + due Tags + Message alerts in InfoTab |
| Actions gated by permissions (D-21) | PASS | v-if on all mutation buttons per role |

**Artifact verification:**

| Artifact | Path | Exists |
|----------|------|--------|
| CalibrationListPage | `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` | ✅ |
| CalibrationDetailPage | `frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue` | ✅ |
| CalibrationInfoTab | `frontend/src/modules/calibrations/components/CalibrationInfoTab.vue` | ✅ |
| CalibrationCertificateTab | `frontend/src/modules/calibrations/components/CalibrationCertificateTab.vue` | ✅ |
| CalibrationTimelineTab | `frontend/src/modules/calibrations/components/CalibrationTimelineTab.vue` | ✅ |
| CalibrationCreateDialog | `frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue` | ✅ |
| CalibrationConcludeDialog | `frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue` | ✅ |

**Plan 08-04: PASSED** (7/7 artifacts, 9/9 truths)

---

## Final Verdict

| Plan | Artifacts Verified | Truths Verified | Status |
|------|------------------|-----------------|--------|
| 08-01 — Data Layer | 11/11 | 6/6 | ✅ PASSED |
| 08-02 — API Layer | 11/11 | 8/8 | ✅ PASSED |
| 08-03 — Frontend Data | 5/5 | 5/5 | ✅ PASSED |
| 08-04 — Frontend UI | 7/7 | 9/9 | ✅ PASSED |
| **Total** | **34/34** | **28/28** | **✅ PASSED** |

All 34 artifact paths exist on disk. All 28 must_have truths from the 4 plans are satisfied. The SUMMARY files (08-01 through 08-04) confirm automated verification steps passed including migration up/down, route listing, command execution, type-checking, and Vite build.