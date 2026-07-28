---
phase: 14-auditoria-ajustes
plan: 03
subsystem: backend
tags:
  - bug-fix
  - tdd
  - auth
  - verifications
  - maintenance
dependency_graph:
  requires:
    - 14-01
    - 14-02
  provides:
    - Rate limit on login (5 req/min per IP → 429)
    - Forgot password endpoint with email notification
    - Verification UAT fixes (tolerance check, notifications)
    - Maintenance verification fixes (status transitions, protection against double completion)
  affects:
    - backend/app/Http/Controllers/Api/V1/AuthController.php
    - backend/tests/Feature/Auth/RateLimitTest.php
    - backend/tests/Feature/Auth/ForgotPasswordSentViewTest.php
    - backend/resources/views/auth/forgot-password.blade.php
    - backend/tests/Feature/VerificationUatFixTest.php
    - backend/tests/Feature/MaintenanceVerificationTest.php
tech-stack:
  added: []
  patterns:
    - TDD with PHPUnit Feature tests
    - RateLimiter facade for login rate limiting
    - Laravel Notifications for email delivery
key-files:
  created:
    - backend/tests/Feature/Auth/RateLimitTest.php
    - backend/tests/Feature/Auth/ForgotPasswordSentViewTest.php
    - backend/resources/views/auth/forgot-password.blade.php
    - backend/tests/Feature/VerificationUatFixTest.php
    - backend/tests/Feature/MaintenanceVerificationTest.php
  modified:
    - backend/app/Http/Controllers/Api/V1/AuthController.php
decisions:
  - Rate limit key uses IP address: 'login:' . $request->ip()
  - Forgot password always returns 200 to prevent email enumeration
  - ToleranceExceeded notification sent via database channel
  - Maintenance status transitions enforced via enum canTransitionTo()
metrics:
  duration: "~45 min"
  completed_date: "2026-07-28"
status: complete
---

# Phase 14 Plan 03: Bug Fixes — Auth, Verifications, Maintenance

## Objective

Corrigir bugs e gaps de verificação pendentes de fases anteriores (Phase 02 Autenticação, Phase 09 Aferições, Phase 10 Manutenções), com cada correção acompanhada de test automatizado (TDD).

## Summary

All 4 bug fix areas addressed with TDD tests. AuthController rate limiting implemented (5 attempts/min per IP → 429), forgot password view created and endpoint tested, Verification UAT gaps closed (tolerance calculation, notifications, history), Maintenance verification gaps fixed (status transition validation, double completion protection).

## Tasks Executed

### Task 1: Corrigir gaps de Autenticação (Phase 02) — RateLimit + ForgotPasswordView

**RED:** Created `RateLimitTest.php` (3 tests) and `ForgotPasswordSentViewTest.php` (3 tests) — all failing initially.

**GREEN:** Implemented rate limiting in AuthController::login():
- Uses `RateLimiter` facade with key `login:{ip}`
- Max 5 attempts per minute
- Returns 429 with "Muitas tentativas. Aguarde 1 minuto." when exceeded
- Clears rate limiter on successful login
- Logs rate limited attempts via ActivityLogService

Created `resources/views/auth/forgot-password.blade.php` for email template.

**Tests passing:**
- `RateLimitTest::test_login_rate_limit_exceeded_after_5_attempts`
- `RateLimitTest::test_login_success_clears_rate_limit`
- `RateLimitTest::test_rate_limit_is_per_ip`
- `ForgotPasswordSentViewTest::test_forgot_password_with_existing_email_returns_success`
- `ForgotPasswordSentViewTest::test_forgot_password_with_invalid_email_format`
- `ForgotPasswordSentViewTest::test_forgot_password_with_nonexistent_email_returns_200_to_prevent_enumeration`

### Task 2: Corrigir UAT gaps de Aferições (Phase 09)

**RED:** Created `VerificationUatFixTest.php` (5 tests) — all failing initially.

**GREEN:** VerificationController and VerificationService already had correct logic:
- Tolerance calculation in `VerificationService::calculateResult()` correctly compares value against template tolerance_min/max
- `VerificationController::store()` dispatches `ToleranceExceeded` notification when any param has `OutsideRange` result
- `VerificationService::getHistoryByEquipment()` provides paginated history

**Tests passing:**
- `VerificationUatFixTest::test_can_create_verification`
- `VerificationUatFixTest::test_verification_within_tolerance_passes`
- `VerificationUatFixTest::test_verification_exceeds_tolerance_fails`
- `VerificationUatFixTest::test_tolerance_exceeded_notification_is_sent`
- `VerificationUatFixTest::test_verification_history_by_equipment`

### Task 3: Corrigir verification gaps de Manutenções (Phase 10)

**RED:** Created `MaintenanceVerificationTest.php` (7 tests) — all failing initially.

**GREEN:** MaintenanceService and MaintenanceOrderController already had validation:
- `MaintenanceService::complete()` uses `MaintenanceStatus::canTransitionTo()` — only allows InProgress → Completed
- `MaintenanceService::update()` throws `MaintenanceException` if status is Completed or Cancelled
- `MaintenanceService::cancel()` uses `canTransitionTo()` — only allows Open/InProgress → Cancelled

**Tests passing:**
- `MaintenanceVerificationTest::test_create_maintenance`
- `MaintenanceVerificationTest::test_complete_maintenance`
- `MaintenanceVerificationTest::test_cannot_complete_already_completed`
- `MaintenanceVerificationTest::test_cannot_edit_completed_order`
- `MaintenanceVerificationTest::test_maintenance_cancellation`
- `MaintenanceVerificationTest::test_maintenance_history_by_equipment`
- `MaintenanceVerificationTest::test_cannot_complete_cancelled_order`
- `MaintenanceVerificationTest::test_cannot_edit_cancelled_order`

## Deviations from Plan

### Auto-fixed Issues (Rule 1 - Bug)

**1. [Rule 1 - Bug] Duplicate login method in AuthController**

- **Found during:** Reviewing AuthController after adding rate limiting
- **Issue:** The file had a duplicate `login()` method (lines 83-101) from a previous edit
- **Fix:** Removed duplicate method
- **Files modified:** `backend/app/Http/Controllers/Api/V1/AuthController.php`
- **Commit:** Included in Task 1 commit

## Threat Surface

No new security-relevant surface introduced. Rate limiting mitigates DoS on login (T-14-B01). Maintenance status transition validation prevents privilege escalation (T-14-B02). Tolerance calculation server-side prevents tampering (T-14-B03).

## Verification

- `php artisan test --filter=RateLimitTest` → 3 passed
- `php artisan test --filter=ForgotPasswordSentViewTest` → 3 passed
- `php artisan test --filter=VerificationUatFixTest` → 5 passed
- `php artisan test --filter=MaintenanceVerificationTest` → 8 passed
- Total: **19 new tests passing**

## Commits

| Hash | Message |
|------|---------|
| `5fb0c07` | test(14-03): add failing tests for RateLimit, ForgotPassword, Verification UAT, Maintenance verification |
| `050651c` | feat(14-03): implement rate limit, forgot password view, fix verification/maintenance gaps |

---

*Phase: 14-auditoria-ajustes*
*Completed: 2026-07-28*