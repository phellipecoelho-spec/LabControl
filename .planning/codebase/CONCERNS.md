# Codebase Concerns

**Analysis Date:** 2026-07-19

## Project Maturity — Skeleton / Scaffolding Only

**Issue:** The project is in a very early pre-implementation state. After Sprint 0 (Foundation), only scaffolding exists with no business logic implemented. The codebase contains approximately 78 empty directories and only 3 files with actual application code (a stub `App.vue`, a placeholder `DashboardPage.vue`, and an empty `Controller.php`).

**Files:**
- `frontend/src/modules/` — 13 module directories, each with 6 empty subdirectories (78 directories, 1 populated file)
- `frontend/src/services/` — empty
- `frontend/src/stores/` — empty
- `frontend/src/composables/` — empty
- `frontend/src/utils/` — empty
- `frontend/src/types/` — empty
- `frontend/src/plugins/` — empty
- `frontend/src/constants/` — empty
- `frontend/src/interfaces/` — empty
- `frontend/src/assets/` — empty
- `backend/app/Http/Controllers/Controller.php` — empty abstract class (8 lines, no methods)
- `backend/app/Providers/AppServiceProvider.php` — empty register() and boot()

**Impact:** The project is not in a runnable state for any business feature. Estimated remaining implementation is 15,000–20,000 lines of business logic.

**Fix approach:** This is expected for the end of Sprint 0. Begin Sprint 1 with infrastructure verification, then Sprint 2 (Authentication) as the first functional module.

---

## Pending Sprint 0 Items — Docker Not Verified

**Issue:** SPRINT-0.md explicitly lists "Ambiente Docker rodando (pendente: composer install + docker compose up)" as unchecked. The Docker environment has never been fully verified to start and serve the application.

**Files:**
- `docs/sprints/SPRINT-0.md` (line 17)
- `docker/docker-compose.yml`
- `scripts/setup.ps1`
- `docker/php/Dockerfile`
- `docker/nginx/default.conf`

**Impact:** The development environment may fail on first startup. The `setup.ps1` script does not run `docker compose up` with the `--build` flag and suppresses all output with `Out-Null`, making debugging difficult.

**Fix approach:**
1. Run `docker compose build php` and `docker compose up -d` manually
2. Verify nginx can reach PHP-FPM on port 9000
3. Verify PostgreSQL accepts connections
4. Run `php artisan migrate` inside the container
5. Remove `Out-Null` suppression from `setup.ps1` for better debugging

---

## Hardcoded Database Credentials in docker-compose.yml

**Issue:** PostgreSQL credentials are hardcoded in `docker/docker-compose.yml`:
- Username: `labcontrol`
- Password: `labcontrol`

**Files:**
- `docker/docker-compose.yml` (lines 27–30, 45–47)

**Impact:** Security risk in any shared or production-like environment. The same password is repeated in the PHP environment section and the PostgreSQL service section.

**Fix approach:** Move credentials to a `.env` file in the `docker/` directory. Use Docker Compose variable interpolation (`${DB_PASSWORD}`). Add `docker/.env` to `.gitignore` (already added).

---

## Application Name Still "Laravel"

**Issue:** The application name defaults to "Laravel" in `backend/config/app.php` (line 16), and the composer.json package name is `laravel/laravel` (line 4). These should reflect the project name "LabControl".

**Files:**
- `backend/config/app.php` (line 16: `'name' => env('APP_NAME', 'Laravel')`)
- `backend/composer.json` (line 4: `"name": "laravel/laravel"`, line 6: `"description": "The skeleton application for the Laravel framework."`)

**Impact:** Inconsistency in branding. The `APP_NAME` env variable should be set to `LabControl`, and composer.json should reflect the project identity.

**Fix approach:**
1. Set `APP_NAME=LabControl` in `backend/.env`
2. Update `composer.json` name to `labcontrol/labcontrol` and description to `"Plataforma modular de gestão laboratorial"`

---

## Locale and Timezone Not Localized for Brazil

**Issue:** The application defaults to English locale and UTC timezone, while the project is a Brazilian Portuguese application:
- `backend/config/app.php` line 68: `'timezone' => 'UTC'`
- `backend/config/app.php` line 81: `'locale' => env('APP_LOCALE', 'en')`
- `backend/config/app.php` line 83: `'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en')`
- `backend/config/app.php` line 85: `'faker_locale' => env('APP_FAKER_LOCALE', 'en_US')`

**Files:**
- `backend/config/app.php`

**Impact:** Dates displayed in UTC instead of America/Sao_Paulo. Validation/faker generates English data. Future localization work will be needed.

**Fix approach:**
1. Set `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR` in `.env`
2. Change timezone to `America/Sao_Paulo`
3. Install Portuguese language packs if needed

---

## Default Laravel Welcome Page Instead of App Shell

**Issue:** The backend renders the default Laravel welcome page (`backend/resources/views/welcome.blade.php`) with 223 lines of inline SVG Laravel branding and minified Tailwind CSS. The frontend SPA (`frontend/src/App.vue`) just renders an empty `<router-view />`.

**Files:**
- `backend/resources/views/welcome.blade.php`
- `backend/routes/web.php` (line 5: `Route::get('/', function () { return view('welcome'); });`)
- `frontend/src/App.vue` (6 lines, empty template)
- `frontend/src/modules/dashboard/pages/DashboardPage.vue` (30 lines, placeholder "LabControl" heading)

**Impact:** Visiting the application shows the Laravel welcome page rather than the application shell. No actual UI is implemented yet.

**Fix approach:** 
1. (Post-Sprint 0) Replace welcome view with the Vue SPA entry point
2. Implement the main application layout shell with PrimeVue components
3. Add meaningful login/dashboard pages

---

## Missing Laravel Sanctum Package

**Issue:** `backend/composer.json` does not include `laravel/sanctum` as a dependency, even though the architecture documentation (`docs/architecture/ARCHITECTURE.md`) explicitly lists "Laravel Sanctum" as the authentication solution. Only `laravel/framework` and `laravel/tinker` are in the require block.

**Files:**
- `backend/composer.json`
- `docs/architecture/ARCHITECTURE.md` (line 31: `| Autenticação | Laravel Sanctum |`)
- `backend/config/auth.php` — only defines session-based `web` guard (line 41–44), no API guard

**Impact:** When Sprint 2 (Authentication) begins, Sanctum installation and configuration will need to happen before any auth code can be written. The auth system is fundamentally incomplete.

**Fix approach:** Install Sanctum via `composer require laravel/sanctum`, publish config, and configure the API guard.

---

## No API Routes Configuration

**Issue:** There is no `backend/routes/api.php` file. All routing is done via `backend/routes/web.php` (single GET `/` returning the welcome view) and `backend/routes/console.php` (single `inspire` command). The architecture specifies `/api/v1/` versioned routes.

**Files:**
- `backend/routes/web.php`
- `backend/routes/api.php` — does not exist
- `docs/architecture/ARCHITECTURE.md` (line 56: `API versionada (/api/v1/)`)

**Impact:** No API infrastructure exists. Service layer cannot be built until API routes are configured. The entire frontend-backend communication pattern is absent.

**Fix approach:** Install Sanctum (which creates `api.php`), create route groups with `/api/v1/` prefix, and implement the first API endpoint.

---

## Session Driver Defaults to Database Instead of Redis

**Issue:** Both session and cache configurations default to `database` driver despite Redis being a core part of the stack:
- `backend/config/session.php` line 21: `'driver' => env('SESSION_DRIVER', 'database')`
- `backend/config/cache.php` line 18: `'default' => env('CACHE_STORE', 'database')`

**Files:**
- `backend/config/session.php`
- `backend/config/cache.php`

**Impact:** Sessions will be stored in the database rather than Redis, adding unnecessary load to PostgreSQL. Redis (already configured in `docker-compose.yml`) should be leveraged.

**Fix approach:** Set `SESSION_DRIVER=redis` and `CACHE_STORE=redis` in `.env`. Verify Redis connection in `config/database.php` (Redis config exists at lines 146–182).

---

## Frontend Missing Development Tooling

**Issue:** The frontend `package.json` only has `@vitejs/plugin-vue` as a dev dependency. There is no linting (ESLint), formatting (Prettier), or testing (Vitest) tooling configured for the frontend.

**Files:**
- `frontend/package.json` (devDependencies: only `@vitejs/plugin-vue`)
- No `.eslintrc*`, `.prettierrc*`, or `vitest.config.*` files exist

**Impact:** No code quality enforcement on the frontend. Inconsistent code style will accumulate as modules are built. No way to run frontend tests.

**Fix approach:** Add ESLint with Vue plugin, Prettier, and Vitest to devDependencies. Configure lint-on-save and format-on-commit hooks.

---

## PWA and Offline Capabilities Not Implemented

**Issue:** The `PLANNER.md` explicitly lists PWA plugin and offline capability as core requirements (Sprint 16, Sprint 17), but:
- No PWA-related packages (`vite-plugin-pwa`, `service-worker`) are in `frontend/package.json`
- No PWA manifest or service worker configuration exists
- No offline/sync strategy (IndexedDB, cache-first) is implemented

**Files:**
- `frontend/package.json`
- `PLANNER.md` (lines 536–541: Sprint 16 PWA, Sprint 17 Offline)

**Impact:** Offline functionality, a stated key differentiator for the product, has not been started and will require significant architecture decisions around data synchronization.

**Fix approach:** Deferred to Sprint 16/17 but should be considered in early architecture decisions (API design, data sync strategy).

---

## Module Structure Over-Engineered for Current State

**Issue:** 13 business modules exist as empty directory shells (auth, calibrations, certificates, dashboard, equipment, inventory, loans, maintenance, movements, reports, settings, users, verifications), each with 6 empty subdirectories (components, pages, routes, services, store, types). The only directory with any file is `modules/dashboard/pages/DashboardPage.vue`.

**Files:**
- `frontend/src/modules/` — 13 module directories × 6 subdirectories = 78 empty directories
- `frontend/src/modules/dashboard/pages/DashboardPage.vue` — only populated file

**Impact:** 78 empty directories add organizational overhead with no benefit. The directory structure should grow organically as modules are implemented, not pre-created.

**Fix approach:** This is acceptable as a forward-looking structure, but the project should prioritize filling module directories with real code rather than creating more empty scaffolding.

---

## Test Coverage is Effectively Zero

**Issue:** Tests are placeholder files with no meaningful coverage:
- `backend/tests/Unit/ExampleTest.php` — `assertTrue(true)`
- `backend/tests/Feature/ExampleTest.php` — asserts GET `/` returns 200 (will break when route changes from welcome page to SPA)
- `backend/tests/TestCase.php` — empty abstract class
- No frontend tests exist; Vitest is not even installed

**Files:**
- `backend/tests/Unit/ExampleTest.php`
- `backend/tests/Feature/ExampleTest.php`
- `backend/tests/TestCase.php`
- No `frontend/src/__tests__/` or `frontend/vitest.config.*`

**Impact:** No regression safety. When development accelerates, there is no automated way to catch regressions.

**Fix approach:**
1. Write tests as each module is implemented (TDD or test-after)
2. Install Vitest for frontend testing before Sprint 2
3. Remove placeholder ExampleTest files when real tests are written
4. Set up PHPUnit with SQLite in-memory database for feature tests

---

## No CI/CD Pipeline Configured

**Issue:** No CI configuration files exist (no `.github/workflows/`, no `.gitlab-ci.yml`, no other CI config). The project has no automated testing, linting, or build verification pipeline.

**Files:**
- No `.github/` directory exists
- No CI configuration files present

**Impact:** No automated quality gates. Every change must be manually verified. Risk of regression increases as the codebase grows.

**Fix approach:** Set up GitHub Actions with:
1. PHPUnit test suite
2. Pint (Laravel code style) check
3. Frontend build check
4. ESLint/Prettier check (once installed)

---

## Hardcoded App Key and Minimal .env Configuration

**Issue:** The `.env` file exists but the application key needs to be generated (the `setup.ps1` script runs `php artisan key:generate`). Many critical environment values remain at defaults (database connection defaults to SQLite, mail defaults to `log`, etc.).

**Files:**
- `backend/.env` — file exists
- `backend/config/database.php` line 20: `'default' => env('DB_CONNECTION', 'sqlite')` — defaults to SQLite, not PostgreSQL
- `backend/.env.example` — exists but may not reflect production settings

**Impact:** Without proper `.env` configuration, the application cannot connect to PostgreSQL (the architecture's chosen database) and will use SQLite in-memory by default.

**Fix approach:** Create a proper `.env` configuration with PostgreSQL connection details, app key, and production-suitable settings. Update `.env.example` to serve as a true reference.

---

## CHANGELOG.md Date Inconsistency

**Issue:** `CHANGELOG.md` shows the initial release date as "2024-07-18", but `backups/` directory exists yet is empty, and today's date context is 2026-07-19.

**Files:**
- `CHANGELOG.md` (line 3: `## [0.1.0] - 2024-07-18`)

**Impact:** Minor — historical inaccuracy. No functional impact.

**Fix approach:** Update the date when the next version is released.

---

## Backups Strategy Missing

**Issue:** A `backups/` directory exists (empty, with `.gitkeep` pattern in `.gitignore`) but no backup strategy or script has been implemented. No database dump procedures, no scheduled backups, and no storage for production backups.

**Files:**
- `backups/` — empty directory
- `.gitignore` (lines 51–52: `backups/*` and `!backups/.gitkeep`)
- No backup scripts in `scripts/`

**Impact:** Production data at risk of loss during development and initial deployment. No disaster recovery capability.

**Fix approach:** Implement backup scripts (shell + scheduled task/cron) that:
1. Dump PostgreSQL to `backups/` with datestamp
2. Archive uploaded files (certificates, photos)
3. Rotate backups (keep last 30 days)

---

## No API Rate Limiting or Request Validation Infrastructure

**Issue:** No rate limiting middleware configured for the API. No custom form requests or validation rules exist. The base Controller is empty — no shared response helpers, no standardized error handling.

**Files:**
- `backend/app/Http/Controllers/Controller.php` — empty abstract class
- No `backend/app/Http/Requests/` directory
- No rate limiter configuration in `backend/bootstrap/app.php`
- No `backend/app/Exceptions/` custom handlers

**Impact:** When API development begins, there are no patterns for request validation, standardized responses, error handling, or abuse prevention. Every controller will need to reinvent these patterns.

**Fix approach:**
1. Add JSON response traits or a base Controller with standardized `success()`, `error()`, `validationError()` methods
2. Create Form Request base class
3. Configure rate limiting in `bootstrap/app.php` before Sprint 2 (Authentication)

---

## Redis Queue Configuration Points to Database Fallback

**Issue:** Queue configuration `backend/config/queue.php` has Redis connection configured (lines 67–74) but the dev script in `composer.json` line 45 starts the queue with `php artisan queue:listen --tries=1`. There is no Horizon or queue monitoring installed for Redis.

**Files:**
- `backend/composer.json` (line 45: `"php artisan queue:listen --tries=1 --timeout=0"`)
- `backend/config/queue.php`

**Impact:** Failed jobs have no dashboard for monitoring. Queue workers run with `--timeout=0` (no timeout) which could lead to hung workers.

**Fix approach:** Install Laravel Horizon for queue monitoring once Redis is configured as the queue driver.

---

*Concerns audit: 2026-07-19*
