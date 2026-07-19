# Testing Patterns

**Analysis Date:** 2026-07-19

## State of Testing

The project has testing infrastructure configured only for the **backend (Laravel/PHP)**. The **frontend (Vue 3/TypeScript)** has no test framework, no test files, and no testing dependencies in `package.json`.

| Layer | Framework | Test Files | Status |
|-------|-----------|------------|--------|
| Backend (PHP) | PHPUnit 12 + Mockery | 2 example tests | Scaffolded, ready |
| Frontend (TS) | None | 0 | Not configured |

---

## Backend Testing (Laravel / PHP)

### Test Framework

**Runner:** PHPUnit 12 (`"phpunit/phpunit": "^12.5.12"` in `composer.json`)
**Assertion Library:** PHPUnit built-in assertions
**Mocking:** Mockery (`"mockery/mockery": "^1.6"`)
**Data Generation:** Faker (`"fakerphp/faker": "^1.23"`)
**Testing helpers:** Laravel's `Illuminate\Foundation\Testing\TestCase`

**Config file:** `backend/phpunit.xml`

### Run Commands

```bash
# Run all tests (via Laravel artisan)
php artisan test

# Run all tests (via PHPUnit directly)
./vendor/bin/phpunit

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-html tests/coverage
```

Also defined in `composer.json` scripts:
```json
"test": [
    "@php artisan config:clear --ansi @no_additional_args",
    "@php artisan test"
]
```

### Test Environment

From `backend/phpunit.xml`:

```xml
<env name="APP_ENV" value="testing"/>
<env name="CACHE_STORE" value="array"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="MAIL_MAILER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="TELESCOPE_ENABLED" value="false"/>
```

Key points:
- **SQLite in-memory** database for test isolation
- **Array cache** — no Redis dependency during tests
- **Sync queue** — no queue worker needed
- **Array mail** — no mail server needed

### Test File Organization

**Location:** `backend/tests/`
**Structure:**
```
backend/tests/
├── TestCase.php              # Base test case class
├── Unit/                     # Unit tests
│   └── ExampleTest.php
└── Feature/                  # Feature/integration tests
    └── ExampleTest.php
```

**Naming convention:** `{TestName}Test.php` (PascalCase + Test suffix)

**Namespace mapping** (from `composer.json` autoload-dev):
```json
"Tests\\": "tests/"
```

### Base TestCase

`backend/tests/TestCase.php`:
```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

- **Abstract** — never instantiated directly
- Extends Laravel's `Illuminate\Foundation\Testing\TestCase`
- Currently empty (no custom setup/teardown)
- All project tests should extend `Tests\TestCase`

### Test Suite Organization

From `backend/phpunit.xml`:
```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
</testsuites>
```

**Unit Tests (`tests/Unit/`):**
- Pure PHPUnit tests (extend `PHPUnit\Framework\TestCase`)
- No Laravel application bootstrapping needed
- Test isolated classes, helpers, utilities
- Fast execution

**Feature Tests (`tests/Feature/`):**
- Extend `Tests\TestCase` (Laravel application available)
- Test HTTP endpoints, database interactions, full workflows
- Can use traits like `RefreshDatabase`, `WithFaker`, `DatabaseMigrations`

### Test Structure Patterns

**Unit Test (from `backend/tests/Unit/ExampleTest.php`):**
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

**Feature Test (from `backend/tests/Feature/ExampleTest.php`):**
```php
<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
```

### Patterns to Follow

#### Unit Tests

**Class naming:** `{ClassName}Test` matching the tested class
**Namespace:** `Tests\Unit\{Subdirectory}`
**Base class:** `PHPUnit\Framework\TestCase`

**Typical structure:**
```php
<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\EquipmentService;

class EquipmentServiceTest extends TestCase
{
    public function test_calculates_next_calibration_date(): void
    {
        $service = new EquipmentService();
        $result = $service->calculateNextCalibrationDate(new \DateTime('2024-01-01'), 12);
        
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('2025-01-01', $result->format('Y-m-d'));
    }
}
```

#### Feature Tests

**Class naming:** `{Endpoint/Feature}Test` describing what's tested
**Namespace:** `Tests\Feature\{Subdirectory}`
**Base class:** `Tests\TestCase`

**Use traits as needed:**
- `use RefreshDatabase;` — Reset database between tests (currently commented out in example)
- `use DatabaseMigrations;` — Run migrations before tests
- `use DatabaseTruncation;` — Truncate tables between tests (Laravel 13)

**Typical structure:**
```php
<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_equipment(): void
    {
        User::factory()->create();
        $response = $this->actingAs(User::first())->getJson('/api/v1/equipment');
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => []]);
    }
}
```

### Mocking Patterns

**Framework:** Mockery (bundled with Laravel)

**Mocking a service:**
```php
<?php

use App\Services\CalibrationService;
use Mockery;

$service = Mockery::mock(CalibrationService::class);
$service->shouldReceive('calculateNextDate')
    ->once()
    ->with(\Mockery::type(\DateTime::class))
    ->andReturn(new \DateTime('2025-01-01'));
```

**Partial mocks for existing objects:**
```php
$user = Mockery::mock(User::class)->makePartial();
$user->shouldReceive('isAdmin')->andReturn(true);
```

### Factory Patterns

**Factory location:** `backend/database/factories/{Model}Factory.php`

**Existing factory (`backend/database/factories/UserFactory.php`):**
```php
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

**Key patterns:**
- `fake()` helper for Faker data
- `definition()` returns the default state array
- `static::$password` caches a single password hash for all created users
- Custom state methods return `static` for fluent chaining
- `HasFactory` trait on models enables `Model::factory()->count(N)->create()`

**Creating models in tests:**
```php
// Single
$user = User::factory()->create();

// With overrides
$user = User::factory()->create(['email' => 'test@labcontrol.com']);

// Multiple
$users = User::factory()->count(5)->create();

// Using a state method
$unverifiedUser = User::factory()->unverified()->create();
```

### Seeders

**Location:** `backend/database/seeders/{Name}Seeder.php`

**Existing seeder (`backend/database/seeders/DatabaseSeeder.php`):**
```php
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
```

### Coverage

**Requirements:** Not enforced (no coverage thresholds in `phpunit.xml`)
**View coverage:**
```bash
./vendor/bin/phpunit --coverage-html tests/coverage
```
Then open `backend/tests/coverage/index.html` in a browser.

### Testing Traits Available

| Trait | Purpose | File |
|-------|---------|------|
| `RefreshDatabase` | Migrate + wrap in transaction per test | Laravel built-in |
| `DatabaseMigrations` | Run all migrations before tests | Laravel built-in |
| `DatabaseTruncation` | Truncate tables between tests | Laravel built-in |
| `WithFaker` | Inject Faker instance | Laravel built-in |
| `WithoutMiddleware` | Disable middleware | Laravel built-in |
| `WithoutEvents` | Disable events | Laravel built-in |

---

## Frontend Testing (Vue 3 / TypeScript)

### Current Status

**No test framework configured.** The frontend `package.json` (`frontend/package.json`) does not include any test runner, assertion library, or testing utilities:

```json
{
  "dependencies": {
    "vue": "^3.5.40",
    "primevue": "^5.0.0",
    "pinia": "^4.0.2",
    "vue-router": "^5.2.0",
    "axios": "^1.18.1",
    "echarts": "^6.1.0",
    "vue-echarts": "^8.0.1",
    "primeicons": "^8.0.0",
    "@primeuix/themes": "^3.0.0",
    "@vueuse/core": "^14.3.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^6.0.8"
  }
}
```

No `vitest.config.*`, `jest.config.*`, or any test files exist.

### Recommended Setup

When frontend testing is introduced, the recommended pattern (consistent with the Vite + TypeScript stack) is:

**Test runner:** [Vitest](https://vitest.dev/) (native Vite integration)
**Component testing:** [@vue/test-utils](https://test-utils.vuejs.org/) v2
**DOM environment:** [jsdom](https://github.com/jsdom/jsdom) or [happy-dom](https://github.com/capricorn86/happy-dom)

**Install:**
```bash
npm install -D vitest @vue/test-utils jsdom
```

**Vitest config** (in `frontend/vite.config.ts` or a separate `vitest.config.ts`):
```typescript
/// <reference types="vitest" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: { '@': resolve(__dirname, 'src') },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/tests/setup.ts'],
  },
})
```

### Expected Test File Locations (once added)

**Following frontend module structure:**
```
frontend/src/modules/{module}/
├── components/          # Vue components
├── pages/               # Page components
│   └── DashboardPage.vue
├── services/            # API calls
│   └── __tests__/       # Service tests
├── store/               # Pinia stores
│   └── __tests__/       # Store tests
├── types/               # Types
└── routes/              # Routes
```

**Test file naming:**
- Co-located: `{ComponentName}.spec.ts` or `{ComponentName}.test.ts`
- Or in `__tests__/` directories: `__tests__/{ComponentName}.spec.ts`

### Expected Testing Patterns (Frontend)

**Component test:**
```typescript
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import DashboardPage from '@/modules/dashboard/pages/DashboardPage.vue'

describe('DashboardPage', () => {
  it('renders the title', () => {
    const wrapper = mount(DashboardPage)
    expect(wrapper.text()).toContain('LabControl')
  })
})
```

**Pinia store test:**
```typescript
import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach } from 'vitest'
import { useEquipmentStore } from '@/modules/equipment/store/equipment'

describe('Equipment Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initializes with empty equipment list', () => {
    const store = useEquipmentStore()
    expect(store.equipment).toEqual([])
  })
})
```

**Service/API test (with Axios mocking):**
```typescript
import { describe, it, expect, vi } from 'vitest'
import axios from 'axios'
import { equipmentService } from '@/modules/equipment/services/equipment'

vi.mock('axios')

describe('Equipment Service', () => {
  it('fetches equipment list', async () => {
    const mockData = [{ id: 1, name: 'Micrometer' }]
    vi.mocked(axios.get).mockResolvedValue({ data: mockData })

    const result = await equipmentService.list()
    expect(result).toEqual(mockData)
    expect(axios.get).toHaveBeenCalledWith('/api/v1/equipment')
  })
})
```

---

## Infrastructure Testing

### Docker

No test infrastructure for Docker. `docker-compose.yml` is development/production only.

### Scripts

No tests exist for `scripts/setup.ps1` or any automation scripts.

---

## Test Coverage Gaps

| Area | What's Missing | Risk | Priority |
|------|----------------|------|----------|
| Frontend — Unit | Entirely untested — no test runner configured | Bugs in UI logic go undetected | **High** |
| Frontend — Component | No component tests | Visual regressions, broken interactions | **High** |
| Frontend — Stores | No Pinia store tests | State management bugs | **High** |
| Frontend — API Layer | No service/API tests | API integration issues | **High** |
| Backend — Unit | Only example test exists | Business logic untested | Medium |
| Backend — Feature | Only example test exists | Endpoints and workflows untested | Medium |
| Backend — Coverage | No coverage threshold configured | Untested code can be committed | Low |
| E2E | No E2E framework | User flows untested | Low (post-MVP) |

---

*Testing analysis: 2026-07-19*
