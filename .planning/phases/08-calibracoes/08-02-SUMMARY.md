---
phase: 08-calibracoes
plan: 02
subsystem: backend
tags: [controller, form-request, api-resource, route, command, notification, schedule]

# Dependency graph
requires:
  - phase: 08-calibracoes
    plan: 01
    provides: Calibration models, services, enums, exceptions, migrations
provides:
  - CalibrationController with 7 actions (index, show, store, update, destroy, complete, cancel)
  - CalibrationCertificateController with 4 actions (index, store, download, destroy)
  - StoreCalibrationRequest, UpdateCalibrationRequest, CompleteCalibrationRequest
  - CalibrationResource and CalibrationCollection API transforms
  - CheckCalibrationDue scheduled command for 30-day due date alerts
  - CalibrationDue notification marker class
  - 11 calibration API routes with permission middleware
affects: [frontend-crud, testing]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Controller with static middleware() method for per-action permission gates
    - CalibrationController middleware: calibracoes.{view,create,edit,concluir,cancel}
    - Form Request with Portuguese validation messages and after_or_equal:today
    - API Resource with whenLoaded() for eager-loaded relationships
    - Command with UUID-based notification insertion for admin/supervisor users
    - Schedule registration via AppServiceProvider booted() closure

key-files:
  created:
    - backend/app/Http/Controllers/Api/V1/CalibrationController.php
    - backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php
    - backend/app/Http/Requests/StoreCalibrationRequest.php
    - backend/app/Http/Requests/UpdateCalibrationRequest.php
    - backend/app/Http/Requests/CompleteCalibrationRequest.php
    - backend/app/Http/Resources/CalibrationResource.php
    - backend/app/Http/Resources/CalibrationCollection.php
    - backend/app/Console/Commands/CheckCalibrationDue.php
    - backend/app/Notifications/CalibrationDue.php
  modified:
    - backend/routes/api.php
    - backend/app/Providers/AppServiceProvider.php

key-decisions:
  - "CompleteCalibrationRequest follows simplified pattern — no custom withValidator, just nullable date/certificate/notes fields"
  - "CalibrationDue notification is a marker class only — data stored directly in notifications table JSON"
  - "Certificate download serves file via Storage::disk('public')->download() with findOrFail for route model binding"

requirements-completed: [CAL-01, CAL-02, CAL-03, CAL-04]

coverage:
  - id: D1
    description: "CalibrationController with 7 action methods and permission middleware"
    requirement: CAL-01
    verification:
      - kind: automated_ui
        ref: "php artisan route:list --path=v1/calibrations — 11 routes registered"
        status: pass
      - kind: automated_ui
        ref: "php artisan tinker class_exists — Controller OK"
        status: pass
    human_judgment: false
  - id: D2
    description: "CalibrationCertificateController with 4 actions (index, store, download, destroy)"
    requirement: CAL-02
    verification:
      - kind: automated_ui
        ref: "GET/POST /calibrations/{calibration}/certificates and /{certificate}/download routes present"
        status: pass
    human_judgment: false
  - id: D3
    description: "3 Form Requests with validation rules and Portuguese messages"
    requirement: CAL-01
    verification:
      - kind: automated_ui
        ref: "Files created and registered — StoreCalibrationRequest validates interval_unit in:months,days,hours"
        status: pass
    human_judgment: false
  - id: D4
    description: "CalibrationResource and CalibrationCollection API transforms"
    requirement: CAL-01
    verification:
      - kind: automated_ui
        ref: "Files created — CalibrationResource with whenLoaded for equipment, created_by, certificates"
        status: pass
    human_judgment: false
  - id: D5
    description: "CheckCalibrationDue command with notifications for admin/supervisor"
    requirement: CAL-03
    verification:
      - kind: automated_ui
        ref: "php artisan calibrations:check-due — found 3 calibrations due soon, created notifications"
        status: pass
    human_judgment: false
  - id: D6
    description: "CalibrationDue notification marker class for polymorphic type resolution"
    requirement: CAL-03
    verification:
      - kind: automated_ui
        ref: "File created at App\Notifications\CalibrationDue"
        status: pass
    human_judgment: false
  - id: D7
    description: "11 calibration API routes with auth:sanctum and permission middleware"
    requirement: CAL-04
    verification:
      - kind: automated_ui
        ref: "php artisan route:list --path=v1/calibrations — 11 routes with correct middleware"
        status: pass
    human_judgment: false
  - id: D8
    description: "Schedule registered in AppServiceProvider as daily command"
    requirement: CAL-03
    verification:
      - kind: automated_ui
        ref: "AppServiceProvider booted contains $schedule->command('calibrations:check-due')->daily()"
        status: pass
    human_judgment: false

# Metrics
duration: 13min
completed: 2026-07-25
status: complete
---

# Phase 8: Calibrações — Plan 02 Summary

**Calibration REST API layer with controllers, form requests, API resources, routes, scheduled due-date command, and notification marker class**

## Performance

- **Duration:** 13 min
- **Started:** 2026-07-25T15:05:00Z
- **Completed:** 2026-07-25T15:18:00Z
- **Tasks:** 2
- **Files modified:** 11

## Accomplishments

- **CalibrationController** — 7 action methods (index, show, store, update, destroy, complete, cancel) with permission middleware per D-21: `calibracoes.view`, `calibracoes.create`, `calibracoes.edit`, `calibracoes.concluir`, `calibracoes.cancel`
- **CalibrationCertificateController** — 4 actions (index, store, download, destroy) with auth:sanctum + `calibracoes.edit` permission, file validation (PDF + images, 10MB), Storage::disk('public') download
- **StoreCalibrationRequest** — validates equipment_id, interval_value/unit, scheduled_date after today, optional part_name/responsible/laboratory/notes; Portuguese messages
- **UpdateCalibrationRequest** — same rules with `sometimes` prefix for optional updates
- **CompleteCalibrationRequest** — optional completed_at, certificate_number, responsible, laboratory, notes
- **CalibrationResource** — transforms all calibration fields with whenLoaded() for relationships (equipment, created_by, certificates), accessor values (is_due, is_due_soon)
- **CalibrationCollection** — paginated response with data + meta (current_page, last_page, per_page, total)
- **CheckCalibrationDue command** — `calibrations:check-due` finds completed calibrations with next_due_at within 30 days, creates in-app notifications for admin/supervisor users with calibration details (equipment name, days until due, next_due_at)
- **CalibrationDue notification** — marker class for polymorphic type resolution in notifications table
- **11 API routes registered** — 5 CRUD + 2 custom actions (complete, cancel) + 4 certificate routes (list, upload, download, delete)
- **Daily schedule registered** — `$schedule->command('calibrations:check-due')->daily()` in AppServiceProvider

## Task Commits

Each task was committed atomically:

1. **Task 1: Create CalibrationController + CalibrationCertificateController + Form Requests + API Resources** - `e165d00` (feat)
2. **Task 2: Create routes, CheckCalibrationDue command, CalibrationDue notification, and schedule registration** - `61b48f5` (feat)

## Files Created/Modified

- `backend/app/Http/Controllers/Api/V1/CalibrationController.php` - 7-action controller with permission middleware
- `backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php` - 4-action certificate controller
- `backend/app/Http/Requests/StoreCalibrationRequest.php` - Creation validation with Portuguese messages
- `backend/app/Http/Requests/UpdateCalibrationRequest.php` - Update validation (optional rules)
- `backend/app/Http/Requests/CompleteCalibrationRequest.php` - Complete action validation
- `backend/app/Http/Resources/CalibrationResource.php` - Single calibration JSON transform with whenLoaded
- `backend/app/Http/Resources/CalibrationCollection.php` - Paginated collection with meta
- `backend/app/Console/Commands/CheckCalibrationDue.php` - Daily due-date check command
- `backend/app/Notifications/CalibrationDue.php` - Notification marker class
- `backend/routes/api.php` (modified) - Added 11 calibration routes
- `backend/app/Providers/AppServiceProvider.php` (modified) - Added calibrations:check-due daily schedule

## Decisions Made

- **CompleteCalibrationRequest design:** Follows a simplified pattern without custom withValidator — just nullable date/certificate/notes fields, keeping the form request simple and delegating business logic to CalibrationService
- **CalibrationDue marker class:** Empty notification class needed for polymorphic type resolution in the notifications table. Data is stored directly in the `data` JSON column by the CheckCalibrationDue command
- **Certificate download approach:** Uses Storage::disk('public')->download() with CalibrationCertificate::findOrFail() for certificate retrieval — no route model binding on the certificate parameter since certificates use UUID primary keys

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## Self-Check: PASSED

- [x] All 9 created files verified on disk
- [x] Both commits exist (e165d00, 61b48f5)
- [x] `php artisan route:list` shows 11 calibration routes with correct middleware
- [x] `php artisan tinker` confirms CalibrationController class exists
- [x] `php artisan calibrations:check-due` runs without errors, finds 3 calibrations due soon, creates notifications
- [x] `php artisan calibrations:check-due` creates in-app notifications for admin/supervisor users

## Next Phase Readiness

- API layer complete — ready for Plan 08-03 (Frontend CRUD): CalibrationListPage, CalibrationDetailPage, dialogs, certificates tab, timeline tab, store, service, types, routes, navigation
- All 11 endpoints registered and verified
- Due-date alert command runs successfully and creates notifications
