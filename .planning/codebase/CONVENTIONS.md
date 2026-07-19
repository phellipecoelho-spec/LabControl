# Coding Conventions

**Analysis Date:** 2026-07-19

## Overview

This project has two distinct language ecosystems with different convention sets: **PHP** (backend — Laravel) and **TypeScript/Vue** (frontend — Vue 3). The backend follows Laravel/PHP community conventions; the frontend follows Vue 3 + TypeScript conventions. Many directories exist as architectural scaffolding with no source code yet, so documented patterns are a blend of actual code found and the conventions implied by the scaffolded structure.

---

## Backend Conventions (PHP / Laravel)

### Naming Patterns

**Classes (PascalCase):**
- Models: `User` (`backend/app/Models/User.php`)
- Controllers: `Controller` (`backend/app/Http/Controllers/Controller.php`)
- Factories: `UserFactory` (`backend/database/factories/UserFactory.php`)
- Seeders: `DatabaseSeeder` (`backend/database/seeders/DatabaseSeeder.php`)
- Providers: `AppServiceProvider` (`backend/app/Providers/AppServiceProvider.php`)

**Methods (camelCase):**
```php
public function register(): void   // AppServiceProvider
public function boot(): void        // AppServiceProvider
public function definition(): array // UserFactory
public function run(): void         // DatabaseSeeder
```

**Tests (snake_case method names):**
```php
public function test_that_true_is_true(): void
public function test_the_application_returns_a_successful_response(): void
```

**Database (snake_case):**
As per `docs/architecture/ARCHITECTURE.md`, `snake_case` is used for all database columns and tables.

**Configuration keys (snake_case):**
- `DB_CONNECTION`, `DB_HOST`, `CACHE_STORE`, `SESSION_DRIVER`, etc.
- Used consistently in `.env`, `phpunit.xml`, and config files.

### Code Style

**Formatting:**
- Tool: **Laravel Pint** (`backend/composer.json` requires `"laravel/pint": "^1.27"`)
- No custom `pint.json` found — uses Laravel Pint defaults (PHP-CS-Fixer ruleset with PSR-12 + Laravel conventions)
- EditorConfig enforces: 4 spaces indent, UTF-8, LF line endings, trailing newline (`backend/.editorconfig`)

**Type declarations:**
- All methods use explicit return types (`: void`, `: array`, etc.)
- Method parameters with type hints used where applicable

```php
public function definition(): array
public function unverified(): static
protected function casts(): array
```

**PHP 8 Attributes used:**
```php
// backend/app/Models/User.php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
```

**DocBlocks:**
- PHPDoc used for class-level `@extends` generics
- Method descriptions use standard Laravel docblock format

```php
/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
```

### Import Organization

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
```

- Grouped by source (Laravel facades, project classes)
- Alphabetical ordering within groups
- `use` statements always after namespace declaration

### Error Handling

**Patterns (from Laravel defaults):**
- Exceptions directory exists at `backend/app/Exceptions/` (empty, Laravel default handler)
- Validation via Form Requests (directory scaffolded at `backend/app/Http/Requests/` — empty)
- API Resources for response transformation (`backend/app/Http/Resources/` — empty)
- Standard Laravel exception handling with `Handler` class (framework default)

**To be defined (scaffold only):**
- `backend/app/Exceptions/` — Custom exceptions go here
- `backend/app/Http/Middleware/` — Custom middleware (directory empty)

### Logging

**Framework:** Laravel logging (`backend/config/logging.php`)
**Stack:** Docker logs via stdout in development
**Standard channels:** `stack`, `single`, `daily`, `slack`, `syslog`, `errorlog`

### Models & Database

**Conventions (from ARCHITECTURE.md + actual code):**
- UUIDs as primary keys (stated in architecture, not yet implemented)
- Soft deletes on all tables
- Audit logging on all operations
- `HasFactory` trait on all models for factory support
- `casts()` method for attribute type casting

**Model pattern:**
```php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### Routing

**Conventions:**
- `backend/routes/web.php` — Web routes (currently single welcome route)
- `backend/routes/console.php` — Artisan commands
- API routes file: Not yet created (will be under `routes/api.php` per Laravel convention)
- API versioning: `/api/v1/` path prefix (directory scaffolded at `backend/app/Http/Controllers/Api/V1/`)

### Module / Architecture

**Layered directories (scaffolded, empty):**
- `backend/app/Actions/` — Single-action classes
- `backend/app/Services/` — Business logic services
- `backend/app/Repositories/` — Data access layer
- `backend/app/DTOs/` — Data transfer objects
- `backend/app/Enums/` — PHP Enums
- `backend/app/Events/` — Events (not yet created)
- `backend/app/Listeners/` — Listeners (not yet created)
- `backend/app/Jobs/` — Queued jobs (not yet created)
- `backend/app/Notifications/` — Notifications (not yet created)
- `backend/app/Observers/` — Model observers
- `backend/app/Rules/` — Custom validation rules
- `backend/app/Traits/` — Reusable traits

**API Controllers:**
- Placed under `backend/app/Http/Controllers/Api/V1/` for versioning
- Base `Controller` is abstract at `backend/app/Http/Controllers/Controller.php`

### i18n / Locale

- Default locale: `pt-BR` (Portuguese — `frontend/index.html` has `lang="pt-BR"`)
- Backend locale: `en` (`backend/config/app.php` — can be changed)
- Faker locale: `en_US`

---

## Frontend Conventions (Vue 3 / TypeScript)

### Naming Patterns

**Files:**
- Vue components: `PascalCase.vue` — `DashboardPage.vue`, `App.vue`
- TypeScript modules: `kebab-case.ts` — `global.css`, `env.d.ts`
- TypeScript config files: `index.ts` for barrel modules — `frontend/src/router/index.ts`

**Components (PascalCase):**
- Multi-word names recommended: `DashboardPage`, `EquipmentForm`
- Page components: `{Module}Page.vue` — `DashboardPage.vue` (`frontend/src/modules/dashboard/pages/DashboardPage.vue`)

**Functions/Methods (camelCase):**
- Standard JavaScript/TypeScript camelCase

**Variables (camelCase):**
- Standard JavaScript/TypeScript camelCase

**Types/Interfaces (PascalCase):**
- Types directory at `frontend/src/types/` (empty)
- Interfaces directory at `frontend/src/interfaces/` (empty)

### Code Style

**Formatting:**
- No Prettier or ESLint configured — project gap
- TypeScript strict mode enabled in `tsconfig.json`:
  ```json
  "strict": true,
  "noUnusedLocals": false,
  "noUnusedParameters": false,
  "noFallthroughCasesInSwitch": true
  ```

**EditorConfig applies:**
- 4 spaces indent
- UTF-8, LF line endings

### Component Structure

**Single File Components (SFC):**
```vue
<template>
  <div class="dashboard">
    <h1>LabControl</h1>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
}
</style>
```

**Patterns:**
- `<script setup lang="ts">` — Composition API with TypeScript
- `scoped` styles — Component-scoped CSS
- `class`-based selectors (lowercase-hyphenated)
- Template uses class bindings for styling

### Import Organization (from `main.ts` and `router/index.ts`)

```typescript
// 1. Third-party packages
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'

// 2. Project modules
import router from './router'
import App from './App.vue'

// 3. Styles (after logic imports)
import 'primeicons/primeicons.css'
import './styles/global.css'
```

**Path Aliases:**
- `@/` maps to `src/` (configured in both `vite.config.ts` and `tsconfig.json`)
  ```typescript
  // vite.config.ts
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  }
  ```

### Module Architecture (Frontend)

Each business module follows a consistent directory structure (`frontend/src/modules/{module}/`):

```
dashboard/
├── components/      # Module-specific Vue components
├── pages/          # Page-level components (routable)
├── routes/         # Route definitions for the module
├── services/       # API call functions (Axios)
├── store/          # Pinia stores for module state
└── types/          # TypeScript type definitions
```

**Module discovery (from existing modules):**
- `dashboard/` — Has `pages/DashboardPage.vue`
- `auth/`, `equipment/`, `calibrations/`, `certificates/`, `inventory/`, `loans/`, `maintenance/`, `movements/`, `reports/`, `settings/`, `users/`, `verifications/` — All scaffolded with empty subdirectories

### Lazy Loading

**Route-based code splitting** (from `router/index.ts`):
```typescript
{
  path: '/',
  name: 'dashboard',
  component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
}
```

### State Management

- **Pinia** for global state
- Stores directory at `frontend/src/stores/` (empty)
- Module stores at `frontend/src/modules/{module}/store/` (all empty)

### API Layer

- **Axios** as HTTP client (`frontend/package.json` dependency)
- Services directory at `frontend/src/services/` (empty)
- Module services at `frontend/src/modules/{module}/services/` (all empty)
- API proxy configured in `vite.config.ts`:
  ```typescript
  proxy: {
    '/api': {
      target: 'http://localhost:80',
      changeOrigin: true,
    },
  }
  ```

### Reusable Logic

- Composables directory at `frontend/src/composables/` (empty)
- Utilities at `frontend/src/utils/` (empty)
- Constants at `frontend/src/constants/` (empty)
- Plugins at `frontend/src/plugins/` (empty)

### Plugin Integration

**PrimeVue** (from `frontend/src/main.ts`):
```typescript
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.app-dark',
    },
  },
})
```

**Dark mode:** Toggled via `.app-dark` CSS class on a parent element.
- CSS defined in `frontend/src/styles/global.css`:
  ```css
  .app-dark {
    background-color: #0f172a;
    color: #e2e8f0;
  }
  ```

---

## Cross-Cutting Conventions

### Git

**Ignore patterns (from `.gitignore`):**
- `node_modules/`, `vendor/` — Dependencies
- `dist/`, `.vite/`, `bootstrap/cache/*.php` — Build artifacts
- `.env`, `.env.*.local` — Environment config
- Storage directories: `storage/**` (with `.gitkeep` exceptions)
- Uploads: `certificates/`, `photos/`, `manuals/`, `attachments/`, `reports/`, `exports/`
- IDE files: `.idea/`, `.vscode/`, `*.swp`, `*.swo`

**Commit messages:** Not defined in project standards. Follow conventional commits implied by `CHANGELOG.md` structure.

### Documentation

- Architecture docs: `docs/architecture/ARCHITECTURE.md`
- Sprint docs: `docs/sprints/SPRINT-N.md`
- Decision records: `docs/decisions/` (empty)
- API docs: `docs/api/` (empty)
- QA docs: `docs/qa/` (empty)
- Wireframes: `docs/wireframes/` (empty)
- All docs written in **Portuguese (pt-BR)**

### Development Scripts

**Backend:**
```bash
composer run setup   # Full install
composer run dev     # Start dev servers (Laravel + Vite concurrently)
composer run test    # Run PHPUnit tests
```

**Frontend:**
```bash
npm run dev    # Vite dev server
npm run build  # Production build
```

**Infrastructure:**
- `scripts/setup.ps1` — Automated Docker + npm setup (PowerShell)

---

## Convention Gaps to Address

| Area | Status | Issue |
|------|--------|-------|
| Frontend linting | **Missing** | No ESLint or Prettier configured — add `@typescript-eslint` + `eslint-plugin-vue` |
| Frontend testing | **Missing** | No test runner configured — consider Vitest |
| Commit conventions | **Undefined** | No commit message format enforced — consider Conventional Commits |
| PHP code style | **Default only** | Laravel Pint installed but no custom config (`pint.json`) |
| DocBlock standards | **Inconsistent** | Some files have them, others don't |

---

*Convention analysis: 2026-07-19*
