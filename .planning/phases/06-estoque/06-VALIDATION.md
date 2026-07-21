---
phase: 6
slug: estoque
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-20
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 11.x (backend) + Vitest (frontend) |
| **Config file** | `backend/phpunit.xml` / `frontend/vitest.config.ts` |
| **Quick run command** | `cd backend && php artisan test --filter=Inventory` |
| **Full suite command** | `cd backend && php artisan test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `cd backend && php artisan test --filter=Inventory`
- **After every plan wave:** Run `cd backend && php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | INVT-01 | migration | `cd backend && php artisan migrate --pretend` | ❌ W0 | ⬜ pending |
| 06-01-02 | 01 | 1 | INVT-01, INVT-02 | unit | `cd backend && php artisan test --filter=InventoryItem` | ❌ W0 | ⬜ pending |
| 06-02-01 | 02 | 2 | INVT-01 | feature | `cd backend && php artisan test --filter=InventoryItemController` | ❌ W0 | ⬜ pending |
| 06-02-02 | 02 | 2 | INVT-02 | feature | `cd backend && php artisan test --filter=InventoryMovementController` | ❌ W0 | ⬜ pending |
| 06-03-01 | 03 | 3 | INVT-01, INVT-02 | browser | Manual (Cypress/Playwright not set up) | ❌ W0 | ⬜ pending |

---

## Wave 0 Requirements

- [ ] `backend/tests/Feature/Api/V1/InventoryItemControllerTest.php` — CRUD feature tests for inventory items
- [ ] `backend/tests/Feature/Api/V1/InventoryMovementControllerTest.php` — CRUD feature tests for movements
- [ ] `backend/tests/Unit/Models/InventoryItemTest.php` — Unit tests for model scopes/accessors
- [ ] `backend/tests/Unit/Models/InventoryMovementTest.php` — Unit tests for movement model
- [ ] `backend/tests/Unit/Services/InventoryMovementServiceTest.php` — Unit tests for transactional movement service

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Toast alert on critical stock | INVT-03 | PrimeVue Toast requires browser context | Create movement that drops stock below minimum — verify toast appears |
| DataTable critical row highlighting | INVT-03 | CSS rendering requires browser | Navigate to list page — verify items below min_stock show red highlight |
| Movement dialog form validation | INVT-02 | PrimeVue form components require browser | Open movement dialog — verify type selection, conditional reason field, submission |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
