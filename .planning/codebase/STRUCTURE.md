# Codebase Structure

**Analysis Date:** 2026-07-19

## Directory Layout

```
labcontrol/
├── frontend/           # Vue 3 SPA (Vite + TypeScript + PrimeVue)
├── backend/            # Laravel 13 REST API (PHP 8.3)
├── database/           # SQL reference scripts (manual)
├── docker/             # Docker Compose + container configs
├── docs/               # Project documentation
├── scripts/            # Automation scripts (PowerShell)
├── backups/            # Database dumps (gitignored)
│
├── README.md           # Project overview + setup instructions
├── CHANGELOG.md        # Version history
├── CONTEXT.md          # Original design context/conversation
├── PLANNER.md          # Full architecture plan + roadmap
├── LICENSE             # MIT
└── .gitignore
```

## Directory Purposes

**`frontend/`:**
- Purpose: Single-page application — the user-facing interface
- Contains: Vue 3 SFCs (`.vue`), TypeScript (`.ts`), CSS (`.css`)
- Key files:
  - `src/main.ts` — Bootstrap entry point (creates Vue app, registers Pinia/Vue Router/PrimeVue)
  - `src/App.vue` — Root component (`<router-view />`)
  - `src/router/index.ts` — Vue Router configuration (currently one route: `/` → DashboardPage)
  - `vite.config.ts` — Vite build config (path alias `@/`, dev proxy `/api` → `localhost:80`)
  - `tsconfig.json` — TypeScript config (strict, path alias `@/*`)
  - `package.json` — Dependencies (PrimeVue 5, Pinia 4, Vue Router 5, Axios, ECharts, VueUse)

**`backend/`:**
- Purpose: REST API server — business logic, data persistence, authentication
- Contains: PHP classes (Laravel structure), config, migrations, tests
- Key files:
  - `artisan` — Laravel CLI entry point
  - `bootstrap/app.php` — Application bootstrap (routing, middleware, exception handling)
  - `bootstrap/providers.php` — Service provider registration
  - `composer.json` — PHP dependencies (Laravel 13, PHPUnit 12, Laravel Pint, etc.)
  - `phpunit.xml` — PHPUnit config (SQLite in-memory for tests)
  - `vite.config.js` — Laravel Vite plugin config (mostly for Blade views)

**`backend/app/`:**
- Purpose: Application source code (PSR-4: `App\`)
- Contains: Controllers, Models, Services, Repositories, Actions, Policies, etc.
- Subdirectories:
  - `Models/` — Eloquent models (`User.php` — the only model currently)
  - `Http/Controllers/` — Base `Controller.php` + `Api/V1/` (empty)
  - `Http/Middleware/` — (empty) — Sanctum middleware to be added
  - `Http/Requests/` — (empty) — Form request validation classes
  - `Http/Resources/` — (empty) — API resource transformers
  - `Providers/` — `AppServiceProvider.php` (empty boot/register)
  - `Services/` — (empty) — Business logic layer
  - `Repositories/` — (empty) — Data access layer
  - `Actions/` — (empty) — Single-purpose action classes
  - `Policies/` — (empty) — Authorization policies
  - `Observers/` — (empty) — Eloquent event observers
  - `Jobs/` — (empty) — Queueable jobs
  - `Events/` — (empty) — Event classes
  - `Listeners/` — (empty) — Event listeners
  - `Traits/` — (empty) — Reusable traits
  - `Enums/` — (empty) — PHP enums
  - `Notifications/` — (empty) — Notification classes
  - `Mail/` — (empty) — Mailable classes
  - `Console/` — (empty) — Artisan commands
  - `Rules/` — (empty) — Custom validation rules
  - `Exceptions/` — (empty) — Custom exception classes

**`backend/database/`:**
- Purpose: Migrations, factories, seeders
- Key files:
  - `migrations/0001_01_01_000000_create_users_table.php` — Users table (UUID PK, soft deletes, audit columns)
  - `migrations/0001_01_01_000003_create_roles_and_permissions_tables.php` — Roles, permissions, pivot tables
  - `migrations/0001_01_01_000004_create_activity_logs_table.php` — Activity audit log
  - `factories/UserFactory.php` — User model factory
  - `seeders/DatabaseSeeder.php` — Seeds test user

**`backend/config/`:**
- Purpose: Laravel configuration files
- Key files: `auth.php`, `database.php`, `cache.php`, `session.php`, `queue.php`, `filesystems.php`, `mail.php`, `logging.php`, `services.php`, `app.php`

**`backend/routes/`:**
- Purpose: Route definitions
- `web.php` — Single welcome route (Blade view)
- `console.php` — `inspire` Artisan command
- `api.php` — **NOT YET CREATED** (planned for API routes)

**`backend/tests/`:**
- `TestCase.php` — Base test class
- `Unit/ExampleTest.php` — Basic unit test (`assertTrue`)
- `Feature/ExampleTest.php` — Basic feature test (GET `/` returns 200)

**`database/`:**
- Purpose: Standalone SQL reference (outside Laravel migrations)
- `scripts/01-schema.sql` — Reference schema notes (lists planned tables, not executable DDL)

**`docker/`:**
- Purpose: Containerization configuration
- Key files:
  - `docker-compose.yml` — Defines 4 services: nginx, php, postgres, redis
  - `nginx/default.conf` — Nginx config (root: `backend/public`, PHP-FPM passthrough)
  - `php/Dockerfile` — `php:8.3-fpm-alpine` with pdo_pgsql, zip, bcmath, composer
  - `php/php.ini` — PHP config (50M upload, 256M memory, America/Sao_Paulo TZ)
  - `postgres/init/01-create-databases.sql` — Creates `labcontrol_testing` and `labcontrol_staging` databases

**`docs/`:**
- Purpose: Project documentation
- Subdirectories: `api/`, `architecture/`, `database/`, `decisions/`, `qa/`, `requirements/`, `sprints/`, `wireframes/`
- Key files:
  - `architecture/ARCHITECTURE.md` — Initial architecture document (56 lines)
  - `sprints/SPRINT-0.md` — Sprint 0 deliverables and status

**`scripts/`:**
- Purpose: Automation scripts
- `setup.ps1` — PowerShell setup script (checks Docker, builds PHP image, runs composer install, starts containers, installs frontend deps)

## Key File Locations

**Entry Points:**
- `frontend/src/main.ts`: Vue SPA bootstrap
- `backend/bootstrap/app.php`: Laravel application bootstrap
- `backend/artisan`: CLI entry point

**Configuration:**
- `frontend/vite.config.ts`: Vite build + dev proxy
- `frontend/tsconfig.json`: TypeScript config
- `backend/composer.json`: PHP dependency manifest
- `backend/phpunit.xml`: Test configuration
- `docker/docker-compose.yml`: Infrastructure orchestration

**Core Logic (planned locations):**
- `frontend/src/modules/*/pages/`: Vue page components
- `frontend/src/modules/*/services/`: API call services
- `frontend/src/modules/*/store/`: Pinia stores per module
- `backend/app/Http/Controllers/Api/V1/`: API controllers
- `backend/app/Services/`: Business logic
- `backend/app/Repositories/`: Data access
- `backend/app/Models/`: Eloquent models

**Testing:**
- `backend/tests/Unit/`: PHPUnit unit tests
- `backend/tests/Feature/`: PHPUnit feature tests

## Naming Conventions

**Files:**
- TypeScript/Vue: `camelCase.ts` or `PascalCase.vue` (e.g., `userService.ts`, `DashboardPage.vue`)
- PHP: `PascalCase.php` (e.g., `User.php`, `Controller.php`)
- Blade: `snake_case.blade.php` (e.g., `welcome.blade.php`)
- Database migrations: `YYYY_MM_DD_HHMMSS_create_table_name.php` (Laravel convention)
- SQL scripts: `NN-descriptive-name.sql` (e.g., `01-schema.sql`)

**Directories:**
- Frontend modules: lowercase, singular (`equipment/`, `inventory/`, `loans/`)
- Backend app: PascalCase (`Models/`, `Http/`, `Controllers/`, `Api/`, `V1/`)
- Backend config: lowercase (`config/database.php`)
- Docker service names: lowercase (`nginx`, `php`, `postgres`, `redis`)

**Database:**
- Tables: `snake_case` plural (e.g., `inventory_movements`, `activity_logs`)
- Columns: `snake_case` (e.g., `created_at`, `email_verified_at`, `is_active`)
- Primary keys: `id` (UUID type)
- Foreign keys: `{table}_id` (e.g., `role_id`, `user_id`)
- Audit columns: `created_by`, `updated_by`, `deleted_by` (UUID nullable)

## Where to Add New Code

**New Business Feature (e.g., Equipment module):**
- Frontend page: `frontend/src/modules/equipment/pages/EquipmentListPage.vue`
- Frontend service: `frontend/src/modules/equipment/services/equipmentService.ts`
- Frontend store: `frontend/src/modules/equipment/store/equipmentStore.ts`
- Frontend types: `frontend/src/modules/equipment/types/equipment.ts`
- Backend controller: `backend/app/Http/Controllers/Api/V1/EquipmentController.php`
- Backend service: `backend/app/Services/EquipmentService.php`
- Backend repository: `backend/app/Repositories/EquipmentRepository.php`
- Backend model: `backend/app/Models/Equipment.php`
- Backend migration: `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_equipment_table.php`
- Backend route: `backend/routes/api.php` (add route group)
- Backend test: `backend/tests/Feature/EquipmentTest.php`
- Backend factory: `backend/database/factories/EquipmentFactory.php`

**New Shared/Global Component:**
- Frontend: `frontend/src/components/` (reusable Vue components without module affinity)
- Backend shared logic: `backend/app/Traits/`, `backend/app/Rules/`

**Utilities:**
- Frontend utils: `frontend/src/utils/`
- Frontend composables: `frontend/src/composables/` or `frontend/src/modules/*/composables/`
- Backend helpers: `backend/app/Traits/` or `backend/app/Services/`

**API Routes:**
- Create `backend/routes/api.php` and define:
  ```php
  Route::prefix('api/v1')->group(function () {
      // Module routes here
  });
  ```
- Then add route loading in `backend/bootstrap/app.php`: `api: __DIR__.'/../routes/api.php'`

## Special Directories

**`backend/vendor/`:**
- Purpose: Composer PHP dependencies
- Generated: Yes (by composer install)
- Committed: No (gitignored)

**`frontend/node_modules/`:**
- Purpose: NPM JavaScript dependencies
- Generated: Yes (by npm install)
- Committed: No (gitignored)

**`frontend/dist/`:**
- Purpose: Production build output
- Generated: Yes (by vite build)
- Committed: No (gitignored)

**`backend/storage/`:**
- Purpose: Laravel runtime storage (logs, cache, views, uploaded files)
- Subdirectories: `app/public/`, `certificates/`, `photos/`, `manuals/`, `attachments/`, `reports/`, `exports/`, `framework/`, `logs/`
- Generated: Partially (caches/logs generated at runtime)
- Committed: No (gitignored except `.gitkeep` files)

**`backups/`:**
- Purpose: Database dumps
- Generated: Yes (manual pg_dump)
- Committed: No (gitignored)

**`docs/`:**
- Purpose: Project documentation (not generated code)
- Generated: No
- Committed: Yes

---

*Structure analysis: 2026-07-19*
