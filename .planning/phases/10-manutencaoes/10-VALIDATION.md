---
phase: 10
slug: manutencaoes
status: checked
nyquist_compliant: true
wave_0_complete: true
created: 2026-07-25
last_checker_run: 2026-07-25
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

- [x] `backend/tests/Unit/Services/MaintenanceServiceTest.php` — service unit tests (created in Plan 01 Task 2)
- [x] `backend/tests/Feature/Http/Controllers/Api/V1/MaintenanceOrderControllerTest.php` — HTTP-level tests (created in Plan 02 Task 1)
- [x] `backend/database/factories/MaintenanceOrderFactory.php` — factory for test data (created in Plan 01 Task 2)

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| History tab layout in EquipmentDetailPage | MAINT-02 | PrimeVue DataTable + tab rendering requires visual check | Navigate to equipment, check "Manutenções" tab, verify timeline with status colors |
| Parts pivot selection on close form | D-05 | Multi-select InventoryItem + quantity requires UX verification | Close an order, open parts selector, verify items load and quantity is recorded |
| Permission gating on tab and buttons | D-16, D-17 | Conditional rendering requires visual check | Login as user without manutencoes.view/create, verify tab and buttons are hidden |

---

## Plan Checker Findings (2026-07-25)

### Goal Statement

Implementar o módulo de Manutenções:
- MAINT-01: Usuário pode abrir ordens de manutenção
- MAINT-02: Sistema mantém histórico de manutenções preventivas e corretivas

### Plan Coverage

| Plan | Tasks | Covers Req | Scope Assessment |
|------|-------|------------|------------------|
| 10-01 | 2 | MAINT-01, MAINT-02 | ✅ 2 tasks, ~13 files — within budget |
| 10-02 | 4 (3 code + 1 checkpoint) | MAINT-01, MAINT-02 | ⚠️ 4 tasks, ~19 files — borderline but acceptable for a complete module delivery |

### Decision Coverage

| Decision | Plan(s) | Status | Notes |
|----------|---------|--------|-------|
| D-01: Single type field | 10-01 | ✅ | MaintenanceType enum with preventive/corrective |
| D-02: Status workflow | 10-01 | ✅ | MaintenanceStatus with canTransitionTo() |
| D-03: All fields | 10-01 | ✅ | Migration has all D-03 fields |
| D-04: Interval-based scheduling | 10-01 | ✅ | calculateNextDue() + createNextPreventive() |
| D-05: Pivot maintenance_order_parts | 10-01 | ✅ | Second table in migration with correct FKs |
| D-06/D-07: Opening form (no technician) | 10-02 | ✅ | StoreMaintenanceOrderRequest + OpenDialog fields match |
| D-08: Notification on create | 10-02 | ✅ | MaintenanceOrderCreated notification dispatched in controller |
| D-09: Closure with parts/hours/cost | 10-02 | ✅ | CompleteMaintenanceOrderRequest + CloseDialog |
| D-10: Auto-create next preventive | 10-01 | ✅ | createNextPreventive() in service |
| D-11: Cancellation with notes | 10-01 | ✅ | cancel() accepts reason param |
| D-12: History tab in EquipmentDetailPage | 10-02 | ✅ | MaintenanceHistoryTab at TabPanel value="6" |
| D-13: List page /maintenance | 10-02 | ✅ | MaintenanceListPage with filters |
| D-14: Permissions 4 slugs | 10-01 | ✅ | Seeded in RolePermissionSeeder |
| D-15: Sidebar scaffolded | — | ✅ | Already exists in navigation.ts |
| D-16: Tab gated by perms | 10-02 | ✅ | v-if="authStore.hasPermission('manutencoes.view')" |
| D-17: Buttons gated by perms | 10-02 | ✅ | v-if gates on create/concluir buttons |
| D-18: Database channel notification | 10-02 | ✅ | via(['database']) in MaintenanceOrderCreated |
| D-19: No scheduled command | — | ✅ | No scheduled commands in either plan |

### Gap Analysis

#### 🚨 BLOCKER: StoreMaintenanceOrderRequest type validation rule has wrong value

**Dimension:** task_completeness  
**Plan:** 10-02, Task 1  
**File:** `backend/app/Http/Requests/StoreMaintenanceOrderRequest.php` (planned)  

The validation rule for the `type` field says:
```php
'type' => 'required|string|in:preventive,critical'
```

This should be `preventive,corrective`. The value `critical` is a priority value, not a maintenance type. This bug would cause the API to reject ALL corrective maintenance order creations with a 422 validation error, since `corrective` is not in the allowed list `preventive,critical`.

**Fix hint:** Change to `in:preventive,corrective` in StoreMaintenanceOrderRequest.

#### ⚠️ WARNING: equipment_id FK onDelete behavior is ambiguous

**Dimension:** task_completeness  
**Plan:** 10-01, Task 1  
**File:** Migration (planned)  

The plan says "cascade on delete: restricted — do not allow equipment deletion with orders" but then says "Use `constrained('equipments')`". Laravel's `constrained()` defaults to `cascadeOnDelete`, which would delete all maintenance orders when an equipment is deleted. This contradicts the stated intent of restricting deletion.

**Fix hint:** Use `constrained('equipments')->restrictOnDelete()` or explicitly use `noActionOnDelete()` to match the stated intent.

#### ⚠️ WARNING: Plan 10-02 scope at upper limit

**Dimension:** scope_sanity  
**Plan:** 10-02  

4 code tasks + 1 checkpoint task modifying ~19 files. While acceptable for a module-closing plan, the scope is at the high end. The 19 files span both backend and frontend layers.

**Recommendation:** Verify carefully during execution. If context pressure is high, consider splitting into 10-02 (API + frontend data layer) and 10-03 (frontend UI components).

#### ℹ️ INFO: Research question 1 (inventory movement) not explicitly resolved

**Dimension:** research_resolution  
**File:** 10-RESEARCH.md  

Research Open Question 1 asks whether consuming parts should create InventoryMovement records. The RESEARCH.md recommendation says "Flag for planner decision." The plans implicitly resolve this by NOT implementing inventory movement recording (pivot records only). This is a valid choice per the research guidance, but the resolution should be documented explicitly.

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| **Type validation bug blocks corrective orders** | Certain (if not fixed) | High — MAINT-01 broken for corrective type | Fix StoreMaintenanceOrderRequest rule before execution |
| **Cascading delete on equipment** | Medium | Medium — data loss on equipment deletion | Fix FK constraint to restrictOnDelete |
| **Scope pressure in plan 10-02** | Low-Medium | Medium — possible quality degradation | Monitor execution, split if needed |
| **Missing inventory movements for parts** | Low | Low — functional tracking exists via pivot | Acceptable per research recommendation |

### Nyquist Compliance Assessment

| Task | Plan | Wave | Automated Command | Status |
|------|------|------|-------------------|--------|
| 1 | 10-01 | 1 | `cd backend && php artisan test --filter=MaintenanceServiceTest --stop-on-failure` | ✅ |
| 2 | 10-01 | 1 | `cd backend && php artisan test --filter=MaintenanceServiceTest --stop-on-failure` | ✅ |
| 1 | 10-02 | 2 | `cd backend && php artisan test --filter=MaintenanceOrderControllerTest --stop-on-failure` | ✅ |
| 2 | 10-02 | 2 | `cd frontend && npx vue-tsc --noEmit --strict` | ✅ |
| 3 | 10-02 | 2 | `cd frontend && npx vue-tsc --noEmit --strict; if ($?) { cd backend && php artisan test --filter=MaintenanceOrderControllerTest --stop-on-failure }` | ✅ |
| 4 | 10-02 | 2 | checkpoint — no automated required | ✅ |

**Sampling:** Wave 1: 2/2 verified ✅ | Wave 2: 3/3 verified ✅  
**Wave 0:** Test files created in Plan 01 (Wave 1) before Plan 02 (Wave 2) depends on them ✅  
**Overall:** ✅ PASS (1 VALIDATION.md update needed — frontmatter now reflects check status)

### Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 30s
- [x] `nyquist_compliant: true` set in frontmatter

### Verdict

## ISSUES FOUND

**Phase:** 10-manutencaoes  
**Plans checked:** 2 (10-01, 10-02)  
**Issues:** 1 blocker, 2 warnings, 1 info  

### Blockers (must fix before execution)

**1. [task_completeness] StoreMaintenanceOrderRequest type validation uses wrong value**
- Plan: 10-02, Task 1
- File: `StoreMaintenanceOrderRequest.php` (planned)
- Detail: Validation rule `in:preventive,critical` should be `in:preventive,corrective`. The value `critical` is a priority level, not a maintenance type. This would break all corrective order creation.
- Fix: Change to `'type' => 'required|string|in:preventive,corrective'`

### Warnings (should fix)

**1. [task_completeness] equipment_id FK onDelete behavior ambiguous**
- Plan: 10-01, Task 1
- Detail: Plan says "restrict on delete" but `constrained()` defaults to cascade. Use explicit `restrictOnDelete()`.
- Fix: `$table->foreignUuid('equipment_id')->constrained('equipments')->restrictOnDelete()`

**2. [scope_sanity] Plan 10-02 at upper limit (4 tasks + checkpoint, ~19 files)**
- Plan: 10-02
- Detail: 4 code tasks + 1 checkpoint is at the high end. Monitor execution quality.
- Fix: Consider splitting if context pressure becomes evident during execution.

### Info
- Research Open Question 1 (inventory movement) implicitly resolved as "pivot only" — document resolution explicitly in plans.

### Recommendation

Fix the **1 blocker** (type validation value) before execution. The 2 warnings are advisory. After the fix, plans are approved for execution as a two-plan phase (Wave 1 + Wave 2).

**Routes to correction:**
1. Fix `in:preventive,critical` → `in:preventive,corrective` in plan 10-02 Task 1 action
2. (Optional) Fix FK constraint to use `restrictOnDelete()` in plan 10-01 Task 1 action
3. (Optional) Document Research Q1 resolution in plan notes

After fixes, run `/gsd-execute-phase 10`.
