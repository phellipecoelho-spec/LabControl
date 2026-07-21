---
phase: 07-emprestimos
plan: 01
subsystem: backend
tags:
  - database
  - migration
  - model
  - enum
  - service
  - factory
  - seeder
dependency_graph:
  requires:
    - phase-05 (equipments table)
    - phase-03 (users table)
  provides:
    - loans table
    - equipment_loan table
    - notifications table
    - models for loan module
    - LoanService for business logic
  affects:
    - phase-07-02 (loan API controllers)
    - phase-07-03 (frontend loan pages)
    - phase-07-04 (overdue notifications command)
tech-stack:
  added:
    - PHP 8 enum (LoanStatus with string backing)
    - Custom pivot model (EquipmentLoan extending Pivot)
    - Transactional service pattern (LoanService)
  patterns:
    - Compound migration with multiple tables
    - State machine validation via canTransitionTo()
    - Partial return tracking per pivot item
key-files:
  created:
    - backend/database/migrations/2026_07_21_000001_create_loans_tables.php
    - backend/database/migrations/2026_07_21_000002_create_notifications_table.php
    - backend/app/Enums/LoanStatus.php
    - backend/app/Models/Loan.php
    - backend/app/Models/EquipmentLoan.php
    - backend/database/factories/LoanFactory.php
    - backend/database/seeders/LoanSeeder.php
    - backend/app/Exceptions/LoanException.php
    - backend/app/Services/LoanService.php
  modified:
    - backend/database/seeders/DatabaseSeeder.php
decisions:
  - "EquipmentLoan extends Pivot (not Model) because belongsToMany using() requires Pivot subclass for fromRawAttributes() hydration"
  - "Conflict detection uses triple-where: loan overlaps start, overlaps end, or fully contains requested period"
  - "autoReturnAll() as separate method for batch returns instead of iterative returnItem calls"
  - "notifications table uses text column for data (JSON string) instead of jsonb for broader compatibility with Laravel's default notification system"
metrics:
  duration_minutes: 12
  completed_date: "2026-07-21"
  files_created: 9
  files_modified: 1
  lines_added: 843
status: complete
---

# Phase 7 Plan 1: Empréstimos — Database & Models Summary

**One-liner:** Migration compound com tabelas loans/equipment_loan/notifications, models Loan/EquipmentLoan com relacionamentos e scopes, enum LoanStatus com validação de transições, LoanService transacional com create/activate/returnItem/cancel, factory com withItems para testes, e seeder com 10 registros de amostra.

## Files Created

| File | Purpose |
|------|---------|
| `backend/database/migrations/2026_07_21_000001_create_loans_tables.php` | Compound migration: `loans` (12 colunas, 5 índices) + `equipment_loan` pivot (5 colunas, unique constraint, 2 índices) |
| `backend/database/migrations/2026_07_21_000002_create_notifications_table.php` | Tabela `notifications` com schema padrão Laravel + índices para lookup por destinatário |
| `backend/app/Enums/LoanStatus.php` | PHP 8 enum backed by string: Reserved, Active, Returned, Cancelled com transições validadas |
| `backend/app/Models/Loan.php` | Model com HasUuids, SoftDeletes, LogsActivity, 7 relacionamentos, 5 scopes, 3 accessors |
| `backend/app/Models/EquipmentLoan.php` | Pivot model com HasUuids, tracking de returned_at individual por item |
| `backend/database/factories/LoanFactory.php` | Factory com 4 states (reserved/active/returned/cancelled) + withItems() callback para attach |
| `backend/database/seeders/LoanSeeder.php` | Seeder: 10 empréstimos (3 reserved, 3 active, 2 returned, 2 cancelled) com 1-3 equipamentos cada |
| `backend/app/Exceptions/LoanException.php` | Custom exception code 422 com render JSON para respostas de API |
| `backend/app/Services/LoanService.php` | Serviço transacional com create, activate, returnItem, cancel, autoReturnAll, checkOverdue |

## Files Modified

| File | Change |
|------|--------|
| `backend/database/seeders/DatabaseSeeder.php` | Added `LoanSeeder::class` to call array |

## Tasks Executed

| # | Task | Status | Commit |
|---|------|--------|--------|
| 1 | Compound migration — loans + equipment_loan + notifications | ✅ | `da300db` |
| 2 | Models — Loan, EquipmentLoan, LoanStatus enum + Factory + Seeder | ✅ | `008d34f` |
| 3 | LoanService + LoanException | ✅ | `05ba406` |

## Verification

| Check | Result |
|-------|--------|
| `php artisan migrate --force` | ✅ Ran (tables existed from prior batch) |
| Tables exist (loans, equipment_loan, notifications) | ✅ Confirmed via Schema |
| LoanStatus enum label() retorna 'Reservado' | ✅ |
| Active->canTransitionTo(Returned) = true, Returned->canTransitionTo(Active) = false | ✅ |
| Reserved->canTransitionTo(Cancelled) = true | ✅ |
| Cancelled->canTransitionTo(Active) = false | ✅ |
| Loan model instancia sem erros | ✅ |
| EquipmentLoan model instancia sem erros | ✅ |
| LoanService instancia via container | ✅ |
| LoanException instancia com code 422 | ✅ |
| Seeder cria 10 empréstimos com estados variados | ✅ |
| DatabaseSeeder inclui LoanSeeder | ✅ |
| All PHP files pass syntax check | ✅ |

## Key Design Decisions

### Pivot Model: extends Pivot, not Model

The `EquipmentLoan` model extends `Illuminate\Database\Eloquent\Relations\Pivot` instead of `Model`. This is required because the `belongsToMany()->using(EquipmentLoan::class)` call in Loan expects a Pivot subclass for proper hydration via `fromRawAttributes()`. Using `Model` causes a `BadMethodCallException`.

### Equipment Conflict Detection

The `findConflictingEquipment()` private method uses a triple-condition WHERE:
1. Loan period starts within requested period
2. Loan period ends within requested period
3. Loan period fully envelops requested period

This catches all overlap scenarios. Only loans with status `reserved` or `active` are considered.

### Status Transition Validation

Transitions are validated at two levels:
1. **Enum level**: `LoanStatus::canTransitionTo()` defines allowed transitions
2. **Service level**: Each method (activate, returnItem, cancel) checks the current status before proceeding

This provides defense-in-depth for state machine integrity.

## Deviations from Plan

None — plan executed exactly as written with all specified fields, indexes, and methods implemented.

## Threat Surface Scan

No new security-relevant surface introduced. Migration FKs use `constrained()` (safe defaults), LoanService validates all inputs, and status transitions are enforced at both enum and service levels (T-07-01, T-07-02 mitigated). Permissions control (T-07-03) will be addressed at the Controller layer in 07-02.

## Self-Check

| File | Status |
|------|--------|
| `backend/database/migrations/2026_07_21_000001_create_loans_tables.php` | ✅ Exists |
| `backend/database/migrations/2026_07_21_000002_create_notifications_table.php` | ✅ Exists |
| `backend/app/Enums/LoanStatus.php` | ✅ Exists |
| `backend/app/Models/Loan.php` | ✅ Exists |
| `backend/app/Models/EquipmentLoan.php` | ✅ Exists |
| `backend/database/factories/LoanFactory.php` | ✅ Exists |
| `backend/database/seeders/LoanSeeder.php` | ✅ Exists |
| `backend/app/Exceptions/LoanException.php` | ✅ Exists |
| `backend/app/Services/LoanService.php` | ✅ Exists |
| `backend/database/seeders/DatabaseSeeder.php` | ✅ Modified |
| Commit `da300db` | ✅ Exists |
| Commit `008d34f` | ✅ Exists |
| Commit `05ba406` | ✅ Exists |

### Self-Check: PASSED
