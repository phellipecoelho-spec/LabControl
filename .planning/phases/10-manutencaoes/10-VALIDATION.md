---
phase: 10
slug: manutencaoes
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-25
---

# Phase 10 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit ^12.5 |
| **Config file** | `backend/phpunit.xml` |
| **Quick run command** | `cd backend && php artisan test --filter=Maintenance` |
| **Full suite command** | `cd backend && php artisan test` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `cd backend && php artisan test --filter=Maintenance`
- **After every plan wave:** Run `cd backend && php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 10-01-01 | 01 | 1 | MAINT-01 | T-10-01 | N/A | migration | `php artisan migrate --pretend` | ❌ W0 | ⬜ pending |
| 10-01-02 | 01 | 1 | MAINT-01 | T-10-02 | Status restricted to enum values | unit | `php artisan model:show MaintenanceOrder` | ❌ W0 | ⬜ pending |
| 10-01-03 | 01 | 1 | MAINT-01 | T-10-03 | Transactional create() with auth operator | unit | `php artisan test --filter=MaintenanceServiceTest` | ❌ W0 | ⬜ pending |
| 10-02-01 | 02 | 2 | MAINT-01, MAINT-02 | T-10-04 | Permission middleware on all endpoints | unit | `php artisan test --filter=MaintenanceOrderControllerTest` | ❌ W0 | ⬜ pending |
| 10-02-02 | 02 | 2 | MAINT-01 | T-10-05 | N/A | build | `npm run build` | ❌ W0 | ⬜ pending |
| 10-02-03 | 02 | 2 | MAINT-01, MAINT-02 | T-10-06 | Permission gate on tab/buttons | build | `npm run build` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `backend/tests/Unit/Services/MaintenanceServiceTest.php` — service unit tests
- [ ] `backend/tests/Feature/Http/Controllers/Api/V1/MaintenanceOrderControllerTest.php` — HTTP-level tests
- [ ] `backend/database/factories/MaintenanceOrderFactory.php` — factory for test data

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| History tab layout in EquipmentDetailPage | MAINT-02 | PrimeVue DataTable + tab rendering requires visual check | Navigate to equipment, check "Manutenções" tab, verify timeline with status colors |
| Parts pivot selection on close form | D-05 | Multi-select InventoryItem + quantity requires UX verification | Close an order, open parts selector, verify items load and quantity is recorded |
| Permission gating on tab and buttons | D-16, D-17 | Conditional rendering requires visual check | Login as user without manutencoes.view/create, verify tab and buttons are hidden |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
