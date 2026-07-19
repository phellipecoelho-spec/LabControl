# Technology Stack

**Analysis Date:** 2026-07-19

## Languages

**Primary:**
- TypeScript (ES2020) - Frontend application code in `frontend/src/`
- PHP ^8.3 - Backend API code in `backend/app/`

**Secondary:**
- SQL - Database migrations and scripts in `database/`
- JavaScript (ES module) - Backend Vite config `backend/vite.config.js`
- CSS - Global styles in `frontend/src/styles/global.css` and Blade views
- HTML (Blade) - Backend view templates in `backend/resources/views/`

## Runtime

**Environment:**
- Node.js 22 LTS - Frontend dev server and build toolchain
- PHP 8.3 FPM (Alpine) - Backend runtime inside Docker container

**Package Manager:**
- npm - Frontend dependencies
  - Lockfile: `frontend/package-lock.json` (present)
- Composer - Backend PHP dependencies
  - Lockfile: `backend/composer.lock` (present)

## Frameworks

**Core:**
- **Vue 3** (^3.5.40) - Frontend SPA framework (`frontend/package.json`)
- **Laravel 13** (^13.8) - Backend API framework (`backend/composer.json`)

**UI Component Library:**
- **PrimeVue** (^5.0.0) - UI component library with Aura theme preset
- **PrimeIcons** (^8.0.0) - Icon library for PrimeVue
- **@primeuix/themes** (^3.0.0) - Theme system for PrimeVue

**State Management:**
- **Pinia** (^4.0.2) - Vue 3 state management (`frontend/src/main.ts`)

**Routing:**
- **Vue Router** (^5.2.0) - Client-side routing for SPA (`frontend/src/router/index.ts`)

**Charts:**
- **Apache ECharts** (^6.1.0) - Charting library
- **vue-echarts** (^8.0.1) - Vue 3 integration wrapper for ECharts

**HTTP Client:**
- **Axios** (^1.18.1) - HTTP client for API communication with Laravel backend

**Utilities:**
- **@vueuse/core** (^14.3.0) - Vue composition utilities

**Testing:**
- **PHPUnit** (^12.5.12) - PHP test runner (`backend/composer.json` require-dev)
- **Mockery** (^1.6) - PHP mock object framework
- **FakerPHP** (^1.23) - Fake data generator
- **Laravel Pail** (^1.2.5) - Laravel log viewer
- **Collision** (^8.6) - CLI error handling for Artisan

**Build/Dev:**
- **Vite** (^8.0.4 via lockfile) - Frontend build tool (`frontend/vite.config.ts`)
- **@vitejs/plugin-vue** (^6.0.8) - Vue 3 SFC plugin for Vite
- **Laravel Vite Plugin** - Backend asset bundling (`backend/vite.config.js`)
- **Tailwind CSS** (via @tailwindcss/vite) - Backend CSS utility framework (`backend/vite.config.js`)
- **TypeScript** (via tsc) - Type checking (`frontend/tsconfig.json`)
- **Laravel Pint** - PHP code style fixer

## Key Dependencies

**Critical:**
- `vue` (^3.5.40) - Core frontend framework
- `primevue` (^5.0.0) - All UI components across the app
- `laravel/framework` (^13.8) - Entire backend API framework
- `pinia` (^4.0.2) - Application state management
- `vue-router` (^5.2.0) - Route definitions and navigation

**Infrastructure:**
- `axios` (^1.18.1) - HTTP communication between frontend and backend API
- `echarts` (^6.1.0) - Dashboard and report charting
- `postgresql` (PHP extension `pdo_pgsql`) - Database connectivity

**Dev:**
- `@vitejs/plugin-vue` (^6.0.8) - Vue SFC compilation in dev/build
- `phpunit/phpunit` (^12.5.12) - Backend test execution

## Configuration

**Environment:**
- Frontend: Vite dev server on port 5173, API proxy to port 80 (`frontend/vite.config.ts`)
- Backend: Laravel `.env` file (gitignored via `backend/.env` in `.gitignore`)
- All environment configs in `backend/config/*.php` read from `env()` calls

**Build:**
- `frontend/tsconfig.json` - TypeScript compiler options (ES2020 target, strict mode, `@/` path alias)
- `frontend/vite.config.ts` - Vite config with Vue plugin and `@/` resolve alias
- `backend/vite.config.js` - Laravel Vite with Tailwind CSS and Instrument Sans font

## Platform Requirements

**Development:**
- Docker + Docker Compose
- Node.js 22 LTS
- Git
- PowerShell 5.1+ (for `scripts/setup.ps1`)

**Production:**
- Docker Compose deployment (`docker/docker-compose.yml`)
- Nginx reverse proxy + PHP-FPM + PostgreSQL 17 + Redis 7
- Alternative: Laravel-compatible hosting (DigitalOcean, Hostinger VPS, AWS, Hetzner, Oracle Cloud)

## Docker Services

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| nginx | nginx:stable-alpine | 80 | Reverse proxy to PHP-FPM |
| php | php:8.3-fpm-alpine (custom) | 9000 | PHP-FPM with pdo_pgsql, zip, bcmath |
| postgres | postgres:17-alpine | 5432 | Primary database |
| redis | redis:7-alpine | 6379 | Cache and queue backend |

---

*Stack analysis: 2026-07-19*
