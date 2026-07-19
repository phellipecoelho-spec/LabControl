<!-- refreshed: 2026-07-19 -->
# Architecture

**Analysis Date:** 2026-07-19

## System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    SPA Frontend (Vue 3)                      │
│  `frontend/src/`                                             │
├──────────────────┬──────────────────┬───────────────────────┤
│   PrimeVue UI    │    Pinia Store   │    Vue Router         │
│   Components     │    State Mgmt    │    Navigation         │
└────────┬─────────┴────────┬─────────┴──────────┬────────────┘
         │                  │                     │
         │         HTTP REST API (Axios)          │
         │         proxy: /api → localhost:80     │
         ▼                  ▼                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  Backend API (Laravel 13)                    │
│  `backend/`                                                  │
├─────────────────────────────────────────────────────────────┤
│  Nginx → PHP-FPM → Controllers → Services → Repositories    │
│  Auth: Sanctum (session)                                     │
│  Jobs/Queue: Redis-backed                                    │
└─────────────────────────────────────────────────────────────┘
         │
         │  Eloquent ORM
         ▼
┌─────────────────────────────────────────────────────────────┐
│  PostgreSQL 17                        Redis 7                │
│  `docker/postgres/`                   `docker/`              │
├─────────────────────────────────────────────────────────────┤
│  Storage (local Laravel storage/)                            │
│  certificates/  photos/  manuals/  attachments/             │
│  reports/  exports/                                          │
└─────────────────────────────────────────────────────────────┘
```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| SPA Frontend | Vue 3 single-page application, UI rendering | `frontend/src/main.ts` |
| Nginx | Reverse proxy, static files, PHP-FPM passthrough | `docker/nginx/default.conf` |
| PHP-FPM | Laravel runtime, API request handling | `docker/php/Dockerfile` |
| Laravel App | REST API, business rules, ORM, auth, jobs | `backend/` |
| PostgreSQL | Primary data store (all persistent data) | `docker/docker-compose.yml` |
| Redis | Cache, queue driver, session storage | `docker/docker-compose.yml` |

## Pattern Overview

**Overall:** Monorepo with decoupled SPA + REST API backend

**Key Characteristics:**
- **API-first**: Vue SPA communicates exclusively via HTTP REST with the Laravel backend
- **Modular monolith (Laravel)**: Backend is a single deployable unit with internal module boundaries (Models, Services, Repositories, Actions)
- **Modular frontend**: Each business domain (equipment, inventory, loans, etc.) is a self-contained module under `frontend/src/modules/`
- **Containerized**: All services run in Docker Compose (nginx, php, postgres, redis)
- **Session-based auth**: Laravel Sanctum with session guard for SPA authentication
- **PWA-ready**: Vue frontend prepared for offline support via PWA plugin (dependency declared in `PLANNER.md`)

## Layers

**Frontend Presentation Layer:**
- Purpose: Renders UI, handles user interaction, manages client state
- Location: `frontend/src/`
- Contains: Vue 3 SFCs, Pinia stores, Vue Router routes, API service calls, TypeScript types/interfaces
- Depends on: Backend API via HTTP, PrimeVue component library, ECharts for dashboards
- Used by: Browser/Client

**Backend API Layer (Laravel):**
- Purpose: Exposes RESTful API endpoints, enforces business rules, manages data persistence
- Location: `backend/app/`
- Contains: Controllers, Form Requests, API Resources, Services, Repositories, Models, Actions, Policies, Jobs, Events/Listeners, Observers, Traits, Enums, Notifications, Rules
- Depends on: PostgreSQL (Eloquent ORM), Redis (Queue/Cache), Laravel framework
- Used by: SPA Frontend (Axios HTTP calls)

**Database Layer:**
- Purpose: Persistent storage, data integrity, audit trail
- Location: `docker/postgres/`, `backend/database/migrations/`
- Contains: PostgreSQL 17, migration scripts, seeders
- Depends on: Filesystem for storage driver (certificates, photos, etc.)
- Used by: Eloquent ORM

## Data Flow

### Primary Request Path (Read/List)

1. User navigates in Vue Router (`frontend/src/router/index.ts`)
2. Vue component calls service module (e.g., `modules/equipment/services/equipmentService.ts`)
3. Service makes Axios GET request to `/api/v1/equipment`
4. Vite dev server proxies to Nginx at `localhost:80` (`frontend/vite.config.ts` lines 14-19)
5. Nginx forwards to PHP-FPM (`docker/nginx/default.conf` lines 23-28)
6. Laravel matches route, dispatches to Controller (`backend/app/Http/Controllers/Api/V1/`)
7. Controller calls Service layer (`backend/app/Services/`)
8. Service uses Repository/Model (`backend/app/Repositories/`, `backend/app/Models/`)
9. Eloquent queries PostgreSQL
10. Response flows back: JSON → Nginx → Vite proxy → Axios → Component

### Authentication Flow (Session-based Sanctum)

1. User submits credentials (email/password) via login form
2. Axios POST to `/api/v1/auth/login`
3. Laravel validates credentials, starts session
4. Sanctum issues session cookie (HTTP-only, SameSite)
5. Subsequent requests include session cookie automatically
6. Laravel middleware validates session on each request

**State Management:**
- Frontend: Pinia stores (`frontend/src/stores/` and per-module in `modules/*/store/`)
- Backend: Stateless between requests (session stored server-side via cookie)

## Key Abstractions

**Frontend Module pattern:**
- Purpose: Encapsulates a business domain (equipment, inventory, auth, etc.)
- Examples: `frontend/src/modules/equipment/`, `frontend/src/modules/auth/`, `frontend/src/modules/dashboard/`
- Pattern: Each module has its own `components/`, `pages/`, `services/`, `store/`, `types/`, `routes/`, `composables/`
- Currently only `dashboard/` has pages implemented (`DashboardPage.vue`); the rest are empty directory scaffolds

**Backend Service/Repository/Action pattern:**
- Purpose: Separates business logic (Services), data access (Repositories), and single-purpose operations (Actions)
- Location: `backend/app/Services/`, `backend/app/Repositories/`, `backend/app/Actions/`
- Status: Directories exist but are empty (scaffolded for future sprints)
- Pattern: Controllers are thin; logic lives in Services; data queries in Repositories; single operations in Actions

**Laravel API Resource pattern:**
- Purpose: Standardized JSON response formatting
- Location: `backend/app/Http/Resources/`
- Status: Directory exists, empty (to be populated)

**Form Request Validation pattern:**
- Purpose: Validates incoming HTTP requests before they reach controllers
- Location: `backend/app/Http/Requests/`
- Status: Directory exists, empty (to be populated)

## Entry Points

**Frontend Entry:**
- Location: `frontend/src/main.ts`
- Triggers: Browser loads SPA
- Responsibilities: Bootstraps Vue app, registers Pinia, Vue Router, PrimeVue with dark theme preset (Aura), mounts `#app`

**Backend Entry:**
- Location: `backend/bootstrap/app.php`
- Triggers: Every HTTP request
- Responsibilities: Configures Laravel routing (web + console), middleware, exception handling (JSON for `/api/*` routes)

**API Routes:**
- Location: `backend/routes/api.php` (not yet created — currently only `web.php` and `console.php` exist)
- Current status: No API routes defined; planned as `/api/v1/*` per PLANNER.md

**Web Routes:**
- Location: `backend/routes/web.php`
- Current: Single welcome route returning `welcome.blade.php`

## Architectural Constraints

- **Threading:** Single-threaded event loop per request (Laravel/PHP-FPM); one process per request
- **Global state:** No module-level singletons or shared mutable state detected; Laravel uses service container for dependency injection
- **Circular imports:** None detected — the codebase is in early scaffolding phase
- **API versioning:** All endpoints must be prefixed with `/api/v1/` (planned)
- **Primary keys:** UUIDs (not auto-increment integers) for all tables, enforced in migrations
- **Soft delete:** All business tables must use `softDeletes()` with `deleted_by` audit column
- **Audit logging:** Every mutation must log to `activity_logs` table
- **Database naming:** `snake_case` for tables, columns, and indexes
- **Storage isolation:** File uploads stored in `backend/storage/` subdirectories by type (certificates, photos, manuals, attachments, reports, exports)

## Anti-Patterns

### Scattered empty scaffold directories

**What happens:** Both frontend (`frontend/src/modules/`) and backend (`backend/app/`) have extensive directory scaffolds with 10+ empty subdirectories representing planned code locations
**Why it's wrong:** Empty directories provide no value and can cause confusion about what's actually implemented vs. planned
**Do this instead:** Create directories only when a file is ready to be placed there. Use the planning documents (`PLANNER.md`, `docs/sprints/`) to track intended structure instead of filesystem scaffolding.

### Missing API routes

**What happens:** `backend/routes/api.php` does not exist; only `web.php` (welcome page) and `console.php` (inspire command) exist
**Why it's wrong:** The primary interface for the SPA is REST API, but no API routes are defined yet
**Do this instead:** Create `backend/routes/api.php` with the `/api/v1/*` route group as the first step before implementing any controller logic

## Error Handling

**Strategy:** Laravel exceptions rendered as JSON for API routes (configured in `backend/bootstrap/app.php` line 18-20)

**Patterns:**
- Global exception handler renders JSON for `api/*` requests
- Form Request validation returns 422 with validation errors
- Standard Laravel abort helpers for HTTP exceptions (planned)

## Cross-Cutting Concerns

**Logging:** Laravel logging stack (configured in `backend/config/logging.php`); currently using default stack
**Validation:** Laravel Form Requests (directories scaffolded at `backend/app/Http/Requests/`, not yet implemented)
**Authentication:** Laravel Sanctum with session guard (configured in `backend/config/auth.php`);
**CORS:** Not yet configured (no Sanctum middleware or CORS config detected — will be needed for SPA)
**Authorization:** Laravel Policies (directory scaffolded at `backend/app/Policies/`); planned integration with roles/permissions tables

---

*Architecture analysis: 2026-07-19*
