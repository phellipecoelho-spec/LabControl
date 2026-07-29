---
phase: 14-auditoria-ajustes
plan: 01
subsystem: "Cross-cutting"
tags:
  - auditoria
  - tests
  - requirements
  - traceability
dependency_graph:
  requires: []
  provides:
    - Audit coverage tests for 6 modules
    - Updated REQUIREMENTS.md traceability
  affects:
    - backend/tests/Feature/AuditCoverage*.php
    - .planning/REQUIREMENTS.md
tech-stack:
  added: []
  patterns:
    - PHPUnit Feature tests authenticated as admin
    - assertDatabaseHas for activity_logs table
key-files:
  created:
    - backend/tests/Feature/AuditCoverageEquipmentTest.php
    - backend/tests/Feature/AuditCoverageInventoryTest.php
    - backend/tests/Feature/AuditCoverageLoanTest.php
    - backend/tests/Feature/AuditCoverageCalibrationTest.php
    - backend/tests/Feature/AuditCoverageVerificationTest.php
    - backend/tests/Feature/AuditCoverageMaintenanceTest.php
  modified:
    - backend/app/Http/Resources/VerificationResource.php
    - backend/app/Http/Requests/UpdateVerificationRequest.php
    - .planning/REQUIREMENTS.md
decisions:
  - Audit coverage tests authenticate as admin and assert activity_logs entries after CRUD
  - Pre-existing VerificationResource bug fixed ($param->whenLoaded → relationLoaded)
  - Pre-existing UpdateVerificationRequest bug fixed (Gate::allows → hasPermission)
  - REQUIREMENTS.md uses consistent "Complete" status across all 36 implemented requirements
metrics:
  duration: "~40 min"
  completed_date: "2026-07-28"
status: complete
---

# Phase 14 Plan 01: Auditoria Cross-Cutting + Requirements Update

## Objective

Verificar cobertura de LogsActivity trait em todos os módulos CRUD, criar testes de feature que comprovam a auditoria, e atualizar a tabela de rastreabilidade de requisitos.

## Summary

All 7 core models confirmed with LogsActivity trait. Six audit coverage test files created (21 tests, 43 assertions) covering Equipment, Inventory, Loan, Calibration, Verification, and Maintenance modules. Two pre-existing bugs found and fixed (VerificationResource `whenLoaded` on model, UpdateVerificationRequest `Gate::allows`). REQUIREMENTS.md updated with real implementation status (36/38 Complete, 94.7% coverage).

## Tasks Executed

### Task 1: Verificar e garantir cobertura de LogsActivity em todos os módulos

- Verified all 7 core models have LogsActivity trait: Equipment, InventoryItem, Loan, Calibration, Verification, MaintenanceOrder, User
- No modifications needed — all models already use the trait
- Trait hooks into model events (created/updated/deleted) via bootLogsActivity
- Controllers use Model::create(), $model->update(), $model->save() which trigger model events

### Task 2: Criar feature tests de auditoria para cada módulo

Created 6 test files with comprehensive CRUD→ActivityLog assertions:

| Test File | Tests | Actions Covered |
|-----------|-------|-----------------|
| AuditCoverageEquipmentTest | 3 | create, update, delete |
| AuditCoverageInventoryTest | 3 | create, update, delete |
| AuditCoverageLoanTest | 4 | create, update, delete, activate |
| AuditCoverageCalibrationTest | 4 | create, update, delete, complete |
| AuditCoverageVerificationTest | 3 | create, update, delete |
| AuditCoverageMaintenanceTest | 4 | create, update, delete, complete |
| **Total** | **21** | **43 assertions** |

All tests pass: `php artisan test --filter=AuditCoverage` → 21 passed, 0 failures.

### Task 3: Atualizar tabela de rastreabilidade em REQUIREMENTS.md

- Added **Module** and **Verified by** columns to traceability table
- Updated all 38 v1 requirements to reflect actual implementation status
- 36 Complete, 2 Pending (INFRA-03 setup script, INVT-03 stock alerts) = **94.7% coverage**
- Normalized status to consistent "Complete" (was mixed "Completed"/"Complete")
- Mapped each requirement to module category
- Added verification source reference for each requirement

## Deviations from Plan

### Pre-existing Bug Fixes (Rule 1)

**1. [Rule 1 - Bug] VerificationResource::toArray calls `whenLoaded()` on Eloquent model**

- **Found during:** Task 2 — AuditCoverageVerificationTest creation
- **Issue:** `VerificationResource` line 39 called `$param->whenLoaded('template', fn() => [...])` on a `VerificationParam` model instance. The `whenLoaded()` method is only available on `JsonResource` classes, not on Eloquent models. This caused a `BadMethodCallException` (500 error).
- **Fix:** Changed to `$param->relationLoaded('template') ? [...] : null`
- **Files modified:** `backend/app/Http/Resources/VerificationResource.php`
- **Commit:** `ed53546`

**2. [Rule 1 - Bug] UpdateVerificationRequest uses `Gate::allows` for non-existent gate**

- **Found during:** Task 2 — AuditCoverageVerificationTest update test
- **Issue:** `UpdateVerificationRequest::authorize()` used `Gate::allows('afericoes.edit')` which checks for a Laravel Gate/policy that doesn't exist. This always returned false, causing all update requests to the verification endpoint to fail with 403 "This action is unauthorized."
- **Fix:** Changed to `auth()->user()->hasPermission('afericoes.edit')` which checks the application's permission system.
- **Files modified:** `backend/app/Http/Requests/UpdateVerificationRequest.php`
- **Commit:** `ed53546`

## Threat Surface

No new security-relevant surface introduced. The audit tests only read and assert against the `activity_logs` table, which is append-only via the LogsActivity trait.

## Known Stubs

None identified.

## Verification

- `php artisan test --filter=AuditCoverage` → **21 passed, 0 failures** ✅
- REQUIREMENTS.md has Module column ✅
- REQUIREMENTS.md has Verified by column ✅
- All 6 modules tested with create/update/delete→ActivityLog assertions ✅
- Pre-existing ReportExportTest failures unchanged (5 BindingResolutionException — out of scope) ✅

## Commits

| Hash | Message |
|------|---------|
| `68745e1` | chore(14-01): verify LogsActivity coverage across 7 core models |
| `ed53546` | feat(14-01): create audit coverage tests for 6 modules + fix pre-existing bugs |
| `9623d62` | docs(14-01): update REQUIREMENTS.md traceability with real status |
