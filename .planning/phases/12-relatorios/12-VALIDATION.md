---
phase: 12
slug: relatorios
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-27
---

# Phase 12 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit ^12.5 + Laravel test helpers |
| **Config file** | `backend/phpunit.xml` (existing) |
| **Quick run command** | `cd backend && php artisan test --filter=Report` |
| **Full suite command** | `cd backend && php artisan test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=Report`
- **After every plan wave:** Run full suite
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** ~30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 12-01-01 | 01 | 1 | REPT-01 | — | — | unit | `php artisan test --filter=ReportServiceTest::test_generates_equipments_report` | ❌ W0 | ⬜ pending |
| 12-01-02 | 01 | 1 | REPT-01 | — | — | unit | `php artisan test --filter=ReportServiceTest::test_generates_calibrations_report` | ❌ W0 | ⬜ pending |
| 12-01-03 | 01 | 1 | REPT-01 | — | — | unit | `php artisan test --filter=ReportServiceTest::test_generates_inventory_movements_report` | ❌ W0 | ⬜ pending |
| 12-01-04 | 01 | 1 | REPT-01 | — | — | unit | `php artisan test --filter=ReportServiceTest::test_generates_dashboard_export` | ❌ W0 | ⬜ pending |
| 12-01-05 | 01 | 1 | REPT-02 | T-12-01 | Sanctum + permission middleware | feature | `php artisan test --filter=ReportControllerTest::test_unauthenticated_user_cannot_export` | ❌ W0 | ⬜ pending |
| 12-01-06 | 01 | 1 | REPT-02 | T-12-02 | `relatorios.export` permission required | feature | `php artisan test --filter=ReportControllerTest::test_user_without_export_permission_receives_403` | ❌ W0 | ⬜ pending |
| 12-02-01 | 02 | 2 | REPT-01 | — | — | feature | `php artisan test --filter=ReportControllerTest::test_pdf_download_returns_valid_pdf` | ❌ W0 | ⬜ pending |
| 12-02-02 | 02 | 2 | REPT-01 | — | — | feature | `php artisan test --filter=ReportControllerTest::test_xlsx_download_returns_valid_spreadsheet` | ❌ W0 | ⬜ pending |
| 12-02-03 | 02 | 2 | REPT-01 | — | — | feature | `php artisan test --filter=ReportControllerTest::test_csv_download_returns_valid_csv` | ❌ W0 | ⬜ pending |
| 12-02-04 | 02 | 2 | REPT-02 | — | — | feature | `php artisan test --filter=ReportControllerTest::test_report_respects_date_filter` | ❌ W0 | ⬜ pending |
| 12-02-05 | 02 | 2 | REPT-02 | T-12-03 | CSV injection prevention | unit | `php artisan test --filter=ReportServiceTest::test_csv_injection_prevention` | ❌ W0 | ⬜ pending |
| 12-02-06 | 02 | 2 | REPT-02 | — | — | feature | `php artisan test --filter=ReportControllerTest::test_filename_follows_convention` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `backend/tests/Feature/ReportControllerTest.php` — covers all REPT-01 and REPT-02 feature test cases
- [ ] `backend/tests/Unit/Services/ReportServiceTest.php` — covers individual report service methods
- [ ] `backend/tests/Feature/ReportExportTest.php` — integration tests for Export classes

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| PDF visual layout renders correctly | REPT-01 | DomPDF rendering differences per environment; automated tests can verify file type but not visual fidelity | Open generated PDF, verify table borders, column alignment, Portuguese characters, logo, page margins |

---

## Validation Sign-Off

- [ ] All tasks have automated verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
