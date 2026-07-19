---
phase: 03
slug: usuarios-permissoes
status: draft
nyquist_compliant: true
wave_0_complete: false
created: 2026-07-19
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 12.x |
| **Config file** | `backend/phpunit.xml` |
| **Quick run command** | `php artisan test --filter=User\|Role\|Profile\|ActivityLog` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=<test_class>`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | Status |
|---------|------|------|-------------|-----------|-------------------|--------|
| 03-01-01 | 01 | 1 | USERS-01 | manual | `php artisan migrate --force` | ⬜ pending |
| 03-01-02 | 01 | 1 | USERS-01 | manual | — | ⬜ pending |
| 03-01-03 | 01 | 1 | USERS-01 | manual | — | ⬜ pending |
| 03-01-04 | 01 | 1 | USERS-01/02 | feature | `php artisan route:list --path=v1/users; php artisan route:list --path=v1/roles` | ⬜ pending |
| 03-02-01 | 02 | 2 | USERS-01/02 | typescript | `cd frontend; npx vue-tsc --noEmit` | ⬜ pending |
| 03-02-02 | 02 | 2 | USERS-01 | typescript | `cd frontend; npx vue-tsc --noEmit` | ⬜ pending |
| 03-02-03 | 02 | 2 | USERS-02 | typescript | `cd frontend; npx vue-tsc --noEmit` | ⬜ pending |
| 03-02-04 | 02 | 2 | USERS-01/02 | typescript | `cd frontend; npx vue-tsc --noEmit` | ⬜ pending |
| 03-03-01 | 03 | 2 | USERS-03 | manual | — | ⬜ pending |
| 03-03-02 | 03 | 2 | USERS-03 | feature | `php artisan route:list --path=v1/profile` | ⬜ pending |
| 03-03-03 | 03 | 2 | USERS-03 | typescript | `cd frontend; npx vue-tsc --noEmit` | ⬜ pending |
| 03-03-04 | 03 | 2 | USERS-03 | manual | `docker compose exec php php artisan storage:link` | ⬜ pending |
| 03-04-01 | 04 | 2 | LOGS-01 | manual | — | ⬜ pending |
| 03-04-02 | 04 | 2 | LOGS-01 | manual | — | ⬜ pending |
| 03-04-03 | 04 | 2 | LOGS-01/02 | feature | `php artisan route:list --path=v1/logs` | ⬜ pending |
| 03-04-04 | 04 | 2 | LOGS-02 | typescript | `cd frontend; npx vue-tsc --noEmit` | ⬜ pending |
| 03-04-05 | 04 | 2 | LOGS-01/02 | feature | `php artisan test --filter=ActivityLogTest` | ⬜ pending |

---

## Wave 0 Requirements

- [ ] `backend/tests/Feature/Users/UserTest.php` — stubs for USERS-01
- [ ] `backend/tests/Feature/Users/RoleTest.php` — stubs for USERS-02
- [ ] `backend/tests/Feature/Users/ProfileTest.php` — stubs for USERS-03
- [ ] `backend/tests/Feature/ActivityLogTest.php` — stubs for LOGS-01/02

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Avatar upload + display | USERS-03 | Requires visual verification | Upload image via /profile, verify avatar displays correctly, verify old avatar deleted |
| Admin role bypass | USERS-02 | Requires session context | Login as admin, verify can access all endpoints; login as non-admin, verify 403 |
| Timeline visual rendering | LOGS-02 | Requires visual verification | Visit /admin/logs, verify timeline icons/colors, test filters |
| Role protection (admin) | USERS-02 | Requires data state | Attempt to delete admin role via API, verify 403; attempt to delete role with users, verify 422 |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
