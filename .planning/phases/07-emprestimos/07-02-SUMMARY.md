---
phase: 07-emprestimos
plan: 02
subsystem: backend
tags:
  - controller
  - request
  - resource
  - route
  - command
  - schedule
dependency_graph:
  requires:
    - phase-07-01 (loans table, equipment_loan table, notifications table, Loan model, LoanService, LoanStatus enum, LoanException)
    - phase-05 (equipments table, Equipment model)
    - phase-03 (users table, User model, Role model)
  provides:
    - LoanController API (8 endpoints)
    - StoreLoanRequest validation
    - UpdateLoanRequest validation
    - ReturnLoanItemRequest validation
    - LoanResource serialization
    - LoanCollection with summary meta
    - CheckOverdueLoans command
    - loans:check-overdue daily schedule
  affects:
    - backend/routes/api.php (new loan routes)
    - backend/app/Providers/AppServiceProvider.php (schedule registration)
tech-stack:
  added:
    - Laravel Form Request validation (3 new requests)
    - Laravel API Resource (LoanResource + LoanCollection)
    - Laravel Console Command (CheckOverdueLoans)
  patterns:
    - Static middleware() pattern with permission-based access control
    - LoanService delegation for transactional operations
    - LoanException handling for business rule violations
    - after() validation hook in ReturnLoanItemRequest
key-files:
  created:
    - backend/app/Http/Controllers/Api/V1/LoanController.php
    - backend/app/Http/Requests/StoreLoanRequest.php
    - backend/app/Http/Requests/UpdateLoanRequest.php
    - backend/app/Http/Requests/ReturnLoanItemRequest.php
    - backend/app/Http/Resources/LoanResource.php
    - backend/app/Http/Resources/LoanCollection.php
    - backend/app/Console/Commands/CheckOverdueLoans.php
  modified:
    - backend/routes/api.php
    - backend/app/Providers/AppServiceProvider.php
decisions:
  - "Permission mapping: emprestimos.view → index/show, emprestimos.create → store, emprestimos.edit → update/destroy, emprestimos.finalizar → activate/returnItem/cancel"
  - "UpdateLoanRequest only allows edit when status is 'reserved' — enforced in controller before update"
  - "ReturnLoanItemRequest uses after() validation hook to verify equipment_id belongs to the loan route parameter"
  - "Schedule registered via AppServiceProvider::boot() using booted() closure instead of Console Kernel"
  - "Notification recipients filtered by Role slug (admin, supervisor) for overdue loan alerts"
metrics:
  duration: "~25 minutes"
  completed_date: "2026-07-21"
status: complete
---

# Phase 7 Plan 2: Loan API REST + Overdue Notification Command

## Objective

Create the REST API layer for the Loans module — LoanController with full CRUD + custom actions (activate, return, cancel), 3 Form Requests with validation, 2 API Resources for serialization, routes with Sanctum + permission middleware, and a scheduled command for automatic overdue loan notifications.

## Tasks

### Task 1: LoanController + Form Requests + API Resources + Routes

**Status:** ✅ Complete

**Files created:**
- `backend/app/Http/Requests/StoreLoanRequest.php` — Validates: borrower_id (required, exists:users), equipment_ids (required, array, min:1, each exists:equipments), borrowed_at (required, after_or_equal:today), expected_return_at (required, after:borrowed_at), reason/destination/contact/notes/approved_by. Custom pt-BR messages.
- `backend/app/Http/Requests/UpdateLoanRequest.php` — All fields sometimes with nullable/text validations. Custom pt-BR messages.
- `backend/app/Http/Requests/ReturnLoanItemRequest.php` — Validates: equipment_id (required, exists:equipments), returned_at (nullable, date), notes (nullable, max:1000). `after()` hook validates equipment_id belongs to the loan route parameter.
- `backend/app/Http/Resources/LoanResource.php` — Serializes: id, status, status_label, dates, reason, destination, contact, notes, is_overdue, items_count, returned_items_count, progress (0-100). Related: borrower, approved_by, created_by (id/name/email when loaded), equipment (with pivot: id, returned_at, notes, is_returned).
- `backend/app/Http/Resources/LoanCollection.php` — Extends ResourceCollection with $collects = LoanResource::class. Meta includes pagination info + summary (total, active_count, overdue_count).
- `backend/app/Http/Controllers/Api/V1/LoanController.php` — Static middleware() with auth:sanctum + permission middleware (emprestimos.view/create/edit/finalizar). Actions: index (paginated, filtered by search/status/equipment/borrower/date range), show (with all relations), store (delegates to LoanService::create), update (only when reserved), destroy (soft delete with deleted_by), activate/returnItem/cancel (delegate to LoanService, handle LoanException).

**Routes registered (8 endpoints):**
```
GET|HEAD  api/v1/loans               → loans.index
POST      api/v1/loans               → loans.store
GET|HEAD  api/v1/loans/{loan}        → loans.show
PUT|PATCH api/v1/loans/{loan}        → loans.update
DELETE    api/v1/loans/{loan}        → loans.destroy
POST      api/v1/loans/{loan}/activate → loans.activate
POST      api/v1/loans/{loan}/return → loans.return
POST      api/v1/loans/{loan}/cancel → loans.cancel
```

**Verification:** `php artisan route:list --path=v1/loans` — 8 routes confirmed with correct middleware.

### Task 2: CheckOverdueLoans Scheduled Command

**Status:** ✅ Complete

**Files created:**
- `backend/app/Console/Commands/CheckOverdueLoans.php` — Signature: `loans:check-overdue`. Queries overdue loans via LoanService::checkOverdue(). For each loan, finds admin/supervisor users via Role model (slug: admin, supervisor), creates notification records in the `notifications` table with loan details (borrower_name, equipment_count, expected_return_at, days_overdue, message). Logs progress.

**Files modified:**
- `backend/app/Providers/AppServiceProvider.php` — Registers `loans:check-overdue` on daily schedule via `$this->app->booted()` closure.

**Verification:**
- `php artisan loans:check-overdue` — Runs successfully, creates notifications (3 overdue loans found, 3 notifications created for 1 user).
- `php artisan loans:check-overdue --help` — Shows correct description and usage.
- `php artisan schedule:list` — Shows `0 0 * * * php artisan loans:check-overdue` (daily at midnight).

## Verification Summary

| Check | Result |
|-------|--------|
| `route:list --path=v1/loans` | ✅ 8 routes listed with correct middleware |
| `loans:check-overdue` execution | ✅ Runs successfully, creates notifications |
| `loans:check-overdue --help` | ✅ Shows correct signature and description |
| `schedule:list` | ✅ Shows daily schedule registered |

## Deviations from Plan

**None** — plan executed exactly as written.

## Known Stubs

None identified.

## Threat Flags

None identified — all threat surface matches the plan's threat model.

## Self-Check: PASSED

| Check | Status |
|-------|--------|
| `backend/app/Http/Controllers/Api/V1/LoanController.php` exists | ✅ Found |
| `backend/app/Http/Requests/StoreLoanRequest.php` exists | ✅ Found |
| `backend/app/Http/Requests/UpdateLoanRequest.php` exists | ✅ Found |
| `backend/app/Http/Requests/ReturnLoanItemRequest.php` exists | ✅ Found |
| `backend/app/Http/Resources/LoanResource.php` exists | ✅ Found |
| `backend/app/Http/Resources/LoanCollection.php` exists | ✅ Found |
| `backend/app/Console/Commands/CheckOverdueLoans.php` exists | ✅ Found |
| Commit 39ef8ee (Task 1) exists | ✅ Found |
| Commit 0f9e837 (Task 2) exists | ✅ Found |
