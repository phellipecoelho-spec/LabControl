---
phase: 03-usuarios-permissoes
plan: 03
subsystem: api, frontend, profile
tags: laravel, vue, primevue, intervention, image-processing, sanctum
requires:
  - phase: 03-usuarios-permissoes
    provides: users migration with avatar_path, phone, position, department, signature columns
provides:
  - Avatar/image processing service (256x256 WebP cover crop)
  - Profile CRUD API with Sanctum auth
  - Password change with current_password validation
  - Tabbed frontend profile page (Info, Password, Avatar)
  - Avatar upload with 2MB/dimension validation
affects: 03-04 (User Observer integration for avatar cleanup on delete)
tech-stack:
  added:
    - intervention/image (^4.2) for image processing
    - Laravel 13 Illuminate\Image API (Image facade, ImageManager)
  patterns:
    - Service class pattern for image processing
    - Form Request pattern for validation (Portuguese messages)
    - PrimeVue 5 Tabs API (Tabs/TabList/Tab/TabPanels/TabPanel)
key-files:
  created:
    - backend/app/Services/AvatarService.php
    - backend/app/Http/Requests/UpdateProfileRequest.php
    - backend/app/Http/Requests/StoreAvatarRequest.php
    - backend/app/Http/Controllers/Api/V1/ProfileController.php
    - frontend/src/modules/profile/pages/ProfilePage.vue
    - frontend/src/modules/profile/components/ProfileInfoForm.vue
    - frontend/src/modules/profile/components/PasswordChangeForm.vue
    - frontend/src/modules/profile/components/AvatarUploader.vue
  modified:
    - backend/routes/api.php
    - frontend/src/router/routes.ts
    - frontend/src/stores/auth.ts
    - backend/composer.json
    - backend/composer.lock
key-decisions:
  - "Using Illuminate\Support\Facades\Image (Laravel 13 native) with fromUpload() instead of Intervention Facade - $file->image() method not available"
  - "PrimeVue 5 Tabs API (Tabs/TabList/Tab/TabPanels/TabPanel) - TabView/TabPanel API removed in v5"
  - "PrimeVue 5 Message component replaces InlineMessage"
  - "intervention/image ^4.2 installed as runtime dependency for image processing"
requirements-completed:
  - USERS-03
coverage:
  - id: D1
    description: Avatar service with 256x256 WebP cover crop image processing
    requirement: USERS-03
    verification:
      - kind: other
        ref: backend/app/Services/AvatarService.php
        status: pass
    human_judgment: false
  - id: D2
    description: Profile API endpoints (show, update, password, avatar upload/delete)
    requirement: USERS-03
    verification:
      - kind: other
        ref: php artisan route:list --path=v1/profile
        status: pass
    human_judgment: false
  - id: D3
    description: Frontend profile page with 3-tab TabView
    requirement: USERS-03
    verification:
      - kind: other
        ref: frontend/src/modules/profile/pages/ProfilePage.vue
        status: pass
    human_judgment: false
  - id: D4
    description: Avatar upload with 2MB and 128x128 minimum dimension validation
    requirement: USERS-03
    verification:
      - kind: other
        ref: backend/app/Http/Requests/StoreAvatarRequest.php
        status: pass
    human_judgment: false
duration: 35min
completed: 2026-07-19
status: complete
---

# Phase 3 Plan 3: Profile & Avatar Summary

**Avatar service with 256x256 WebP cover crop, profile CRUD API, password change, and PrimeVue 5 tabbed frontend profile page**

## Performance

- **Duration:** ~35 min
- **Started:** 2026-07-19T14:58:00Z
- **Completed:** 2026-07-19T15:33:00Z
- **Tasks:** 4
- **Files modified:** 14

## Accomplishments

- AvatarService with `store()` (256x256 WebP via Laravel 13 Image API), `deleteExisting()`, `url()`, `deleteByPath()` methods
- UpdateProfileRequest with validation for name, email, phone, position, department, signature (Portuguese messages)
- StoreAvatarRequest with image validation (max 2MB, min 128x128, JPEG/PNG/WebP)
- ProfileController with 5 endpoints: show, update, updatePassword, updateAvatar, deleteAvatar
- All profile routes protected with Sanctum authentication
- Frontend ProfilePage.vue with PrimeVue 5 Tabs component (Informações, Senha, Avatar)
- ProfileInfoForm.vue with inline editing and success/error toast notifications
- PasswordChangeForm.vue with current_password validation and form reset
- AvatarUploader.vue with preview, FileUpload basic mode, and remove button
- User interface extended with phone, position, department, signature, avatar_path fields
- /profile route registered with requiresAuth guard

## Task Commits

Each task was committed atomically:

1. **Task 1: AvatarService and Profile Form Requests** - `af86553` (feat)
2. **Task 2: ProfileController and routes** - `0ee832b` (feat)
3. **Task 3: Profile frontend components** - `ec8b2c1` (feat)
4. **Task 4: Profile route, auth store, storage:link** - `5ac95e7` (feat)

## Files Created/Modified

### Backend (created)
- `backend/app/Services/AvatarService.php` - Image processing service (cover crop to 256x256 WebP)
- `backend/app/Http/Requests/UpdateProfileRequest.php` - Profile field validation
- `backend/app/Http/Requests/StoreAvatarRequest.php` - Avatar upload validation (2MB, 128x128 min)
- `backend/app/Http/Controllers/Api/V1/ProfileController.php` - 5 profile API endpoints

### Backend (modified)
- `backend/routes/api.php` - Added 5 profile routes under auth:sanctum group
- `backend/composer.json` - Added intervention/image dependency
- `backend/composer.lock` - Updated with intervention/image and intervention/gif

### Frontend (created)
- `frontend/src/modules/profile/pages/ProfilePage.vue` - Tabbed profile page
- `frontend/src/modules/profile/components/ProfileInfoForm.vue` - Editable profile fields
- `frontend/src/modules/profile/components/PasswordChangeForm.vue` - Password change form
- `frontend/src/modules/profile/components/AvatarUploader.vue` - Avatar upload with preview

### Frontend (modified)
- `frontend/src/router/routes.ts` - Added /profile route
- `frontend/src/stores/auth.ts` - Extended User interface with profile fields

## Decisions Made

- Used `Illuminate\Support\Facades\Image::fromUpload()` instead of `$file->image()` — the `image()` method doesn't exist on UploadedFile in this Laravel 13 version
- Used PrimeVue 5's new Tabs API (`Tabs`/`TabList`/`Tab`/`TabPanels`/`TabPanel`) instead of old `TabView`/`TabPanel` — TabView was removed in PrimeVue 5
- Used `primevue/message` instead of `primevue/inlinemessage` — InlineMessage was renamed in PrimeVue 5
- Used `@uploader` event instead of `@select` on FileUpload with `customUpload` — PrimeVue 5 API change
- Installed `intervention/image ^4.2` as runtime dependency — required for Laravel 13 Image API facade

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] PrimeVue 5 component API migration**
- **Found during:** Task 3 (Profile frontend components)
- **Issue:** Plan specified `TabView`/`TabPanel` and `InlineMessage` components which were removed/renamed in PrimeVue 5.0.0
- **Fix:** Migrated to PrimeVue 5 Tabs API (`Tabs`/`TabList`/`Tab`/`TabPanels`/`TabPanel`) and `Message` component
- **Files modified:** ProfilePage.vue, ProfileInfoForm.vue, PasswordChangeForm.vue, AvatarUploader.vue
- **Verification:** `npx vue-tsc --noEmit` compiles cleanly (only pre-existing errors remain)
- **Committed in:** `5ac95e7` (Task 4 commit)

**2. [Rule 3 - Blocking] Intervention/image installation required**
- **Found during:** Pre-task setup
- **Issue:** `intervention/image` and `intervention/image-laravel` packages not installed; `$file->image()` method not available
- **Fix:** Installed `intervention/image ^4.2` via composer, used `Image::fromUpload()` facade instead of `$file->image()` or `Image::read()`
- **Files modified:** composer.json, composer.lock
- **Verification:** Route list command succeeds, autoloader functional
- **Committed in:** `af86553` (Task 1 commit)

---

**Total deviations:** 2 auto-fixed (both Rule 3 - Blocking)
**Impact on plan:** Both fixes were necessary for compatibility with installed package versions. No scope creep.

## Issues Encountered

- Docker container composer.lock file permissions issue required `docker compose exec -u root` to install `intervention/image`
- Composer dump-autoload took longer than expected but completed successfully
- Pre-existing TypeScript errors in `PasswordInput.vue` and `router/index.ts` (out of scope for this plan)

## Next Phase Readiness

- Profile API ready for integration with user management UI (Plan 04)
- Avatar cleanup on user delete will be connected via UserObserver in Plan 04
- All user-facing messages in Portuguese
- Storage symlink already active (`storage:link`)

## Self-Check: PASSED

- All 8 created files verified by existence
- All 4 commits verified in git log
- 5 profile routes verified via `php artisan route:list --path=v1/profile`
- TypeScript compiles without errors in profile components (pre-existing errors in other files unchanged)

---
*Phase: 03-usuarios-permissoes*
*Completed: 2026-07-19*
