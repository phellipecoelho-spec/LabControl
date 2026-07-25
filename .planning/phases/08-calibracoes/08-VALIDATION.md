---
phase: 08
slug: calibracoes
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-25
---

# Phase 08 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit ^12.5.12 |
| **Config file** | `backend/phpunit.xml` |
| **Quick run command** | `php artisan test --filter=Calibration` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=Calibration`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 08-01-01 | 01 | 1 | CAL-01 | T-08-01 | N/A — schema | unit | `php artisan test --filter=CalibrationApiTest` | ❌ W0 | ⬜ pending |
| 08-01-02 | 01 | 1 | CAL-01 | T-08-01 | N/A — model | unit | `php artisan test --filter=CalibrationModelTest` | ❌ W0 | ⬜ pending |
| 08-01-03 | 01 | 1 | CAL-01, CAL-02 | T-08-01 | N/A — seeder | unit | `php artisan test --filter=CalibrationSeederTest` | ❌ W0 | ⬜ pending |
| 08-02-01 | 02 | 2 | CAL-01, CAL-02, CAL-04 | T-08-01 | Auth + permission middleware | feature | `php artisan test --filter=CalibrationApiTest` | ❌ W0 | ⬜ pending |
| 08-02-02 | 02 | 2 | CAL-03 | T-08-01 | Auth + permission middleware | feature | `php artisan test --filter=CalibrationDueTest` | ❌ W0 | ⬜ pending |
| 08-03-01 | 03 | 3 | CAL-01, CAL-04 | T-08-01 | N/A — types/store | unit | `npx vitest run frontend/src/stores/__tests__/calibrationStore.spec.ts` | ❌ W0 | ⬜ pending |
| 08-04-01 | 04 | 4 | CAL-01, CAL-04 | T-08-01 | Permission-gated buttons | manual | N/A — visual | ❌ W0 | ⬜ pending |
| 08-04-02 | 04 | 4 | CAL-02 | T-08-02 | MIME validation | manual | N/A — visual | ❌ W0 | ⬜ pending |
| 08-04-03 | 04 | 4 | CAL-03 | T-08-01 | N/A — indicators | manual | N/A — visual | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/CalibrationApiTest.php` — covers CAL-01 through CAL-04
- [ ] `tests/Feature/CalibrationDueTest.php` — covers CAL-03 (CheckCalibrationDue command)

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Visual indicators for due calibrations | CAL-03 | PrimeVue DataTable row styling + badge rendering requires visual inspection | Open CalibrationListPage, verify calibration with `next_due_at < 30 days` has yellow row highlight |
| Certificate upload modal | CAL-02 | FileUpload component with drag-and-drop + preview requires browser | Open CalibrationConcludeDialog, upload PDF, verify toast success |
| Permission-gated buttons | CAL-01 | PrimeVue v-if with hasPermission requires role context | Login as different roles, verify Create/Conclude/Cancel buttons shown/hidden correctly |

*If none: "All phase behaviors have automated verification."*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending 2026-07-25
