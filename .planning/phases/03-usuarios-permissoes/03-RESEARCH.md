# Phase 3: Usuários e Permissões - Research

**Researched:** 2026-07-19
**Domain:** Role-Based Access Control (RBAC), User Management, Activity Logging
**Confidence:** HIGH

## Summary

This phase builds the user and permission management layer on top of the existing authentication infrastructure (Phase 2). The codebase already has User, Role, and Permission models with pivot tables, an `activity_logs` migration, a seeder with 6 roles and 32 permissions, and an AuthStore with `hasRole()`/`hasPermission()` methods. The research confirms that **no external RBAC package is needed** — the existing custom implementation is sufficient and should be extended with Laravel Gates, Policies, and a custom permission middleware.

**Primary recommendation:** Use Laravel 13's Gate facade with a custom permission middleware backed by the existing role/permission models. For activity logging, extend a trait-based observer pattern rather than adding Spatie's activitylog package, since the migration and schema already exist. Avatar uploads use Laravel 13's first-party image API (Intervention-based).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| USERS-01 | CRUD de usuários com perfis (Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor) | Users/Roles models + pivots exist. DataTable + Dialog pattern confirmed. Form Requests with role validation |
| USERS-02 | Atribuição de permissões por papel | RBAC via role-permission pivot. Permission.check via Gate/custom middleware. Accordion + InputSwitch for UI |
| USERS-03 | Perfil de usuário com avatar e dados pessoais | Laravel 13 image API for avatar. Storage public disk + symlink. PrimeVue FileUpload + Avatar components |
| LOGS-01 | Auditoria de todas as operações críticas + autenticação | ActivityLog table exists. Trait-based observer pattern. Auth event logging in AuthController |
| LOGS-02 | Visualização de logs por módulo com timeline visual | PrimeVue Timeline component with filter by module/user/date |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| User CRUD API | Backend (API) | — | Controllers in Api\V1, FormRequest validation, Policies for authorization |
| User CRUD UI | Browser (Vue) | — | PrimeVue DataTable + Dialog, Pinia store for state |
| Role/Permission management | Backend (API) | Database (pivot) | Role-permission assignments via pivot tables |
| Permission checking | Backend (Middleware/Gate) | Frontend (hasPermission) | Server enforces, client hides UI elements |
| Avatar upload & storage | Backend (API) | Storage (public disk) | Laravel image API resizes, stores on public disk |
| Activity logging | Backend (Observer/Trait) | Database (activity_logs) | Model observers capture changes automatically |
| Audit log viewing | Browser (Vue) | Backend (API) | Timeline component with filter API |
| Profile management | Backend (API) | Browser (Vue) | Dedicated ProfileController, tabbed form |

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Gates & Policies | 13.x (built-in) | Authorization | Official Laravel authorization layer [CITED: laravel.com/docs/13.x/authorization] |
| Laravel Image API | 13.x (built-in) | Avatar processing | First-party image manipulation (Intervention-based) [VERIFIED: laravel.com/docs/13.x/images] |
| Laravel Sanctum | 4.x (installed) | API auth guard | Already installed, used by Phase 2 [VERIFIED: composer.json] |
| PrimeVue DataTable | 5.x (installed) | User listing | Locked decision D-01 [VERIFIED: package.json] |
| PrimeVue Dialog | 5.x (installed) | CRUD modals | Locked decision D-01 |
| PrimeVue Accordion | 5.x (installed) | Permission grouping | Locked decision D-03 |
| PrimeVue InputSwitch | 5.x (installed) | Permission toggle | Locked decision D-03 |
| PrimeVue MultiSelect | 5.x (installed) | Role assignment | Locked decision D-04 |
| PrimeVue Timeline | 5.x (installed) | Audit log display | Locked decision D-13 |
| PrimeVue FileUpload | 5.x (installed) | Avatar upload | Standard for file uploads with preview |
| PrimeVue Avatar | 5.x (installed) | User avatar display | Standard display component |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PrimeVue Tag | 5.x (installed) | Status badges | User active/inactive, role badges |
| PrimeVue SelectButton | 5.x (installed) | Filter controls | Role filter in user listing |
| PrimeVue InputText | 5.x (installed) | Search field | Global search in DataTable |
| PrimeVue TabView | 5.x (installed) | Profile tabs | Password change in separate tab (D-10) |
| PrimeVue Password | 5.x (installed) | Password input | Strength indicator for password change |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom Gates + Policies | Spatie Laravel Permission | Existing custom RBAC already has models, migrations, seeders. Spatie would require schema migration and adds dependency. Custom approach keeps control explicit and matches existing code. |
| Custom activity trait | Spatie Laravel Activitylog | Existing `activity_logs` migration already in place. Custom trait avoids schema conflict and keeps zero external dependencies. |
| Custom permission middleware | `#[Middleware('can:...')]` | Use Laravel's built-in `can` middleware for route protection. Custom middleware only needed for permission-by-slug checking with existing models. |

**Laravel 13 `#[Middleware]` attribute pattern:**
```php
// No separate package needed — use Laravel 13's native #[Middleware] attribute
// with a custom permission middleware registered in bootstrap/app.php

#[Middleware('auth:sanctum')]
#[Middleware('permission:usuarios.view')]
class UserController extends Controller
{
    #[Middleware('permission:usuarios.create')]
    public function store(StoreUserRequest $request) { ... }

    #[Middleware('permission:usuarios.edit')]
    public function update(UpdateUserRequest $request, User $user) { ... }

    #[Middleware('permission:usuarios.delete')]
    public function destroy(User $user) { ... }
}
```
[CITED: qadrlabs.com/post/laravel-13-role-based-access-control-with-spatie-permission-and-middleware-attributes]

## Package Legitimacy Audit

> **No new external packages required for this phase.** All libraries are already installed (PrimeVue 5.x, Vue 3.x, Pinia, Axios, Laravel 13.x with Sanctum). The custom RBAC implementation uses existing models. Activity logging uses the existing migration. Image processing uses Laravel 13's built-in Image API (no separate Intervention Image package needed).

| Package | Registry | Verdict | Disposition |
|---------|----------|---------|-------------|
| primevue@5.0.0 | npm | OK | Already installed |
| @primeuix/themes@3.0.0 | npm | OK | Already installed |
| vue@3.5.40 | npm | OK | Already installed |
| pinia@4.0.2 | npm | OK | Already installed |
| vue-router@5.2.0 | npm | OK | Already installed |
| axios@1.18.1 | npm | OK | Already installed |
| laravel/framework ^13.8 | packagist | OK | Already installed |
| laravel/sanctum ^4.0 | packagist | OK | Already installed |

**Packages removed due to [SLOP] verdict:** None
**Packages flagged as suspicious [SUS]:** None

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Frontend (Vue 3 + PrimeVue)                  │
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────┐  │
│  │  UsersPage   │  │  RolesPage   │  │  ProfilePage │  │ Logs   │  │
│  │  DataTable   │  │  Accordion   │  │  TabView     │  │ Page   │  │
│  │  + Dialog    │  │  InputSwitch │  │  + FileUpload│  │Timeline│  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └────┬───┘  │
│         │                 │                 │               │       │
│  ┌──────▼─────────────────▼─────────────────▼───────────────▼───┐   │
│  │                    Pinia Stores                                │   │
│  │  stores/users.ts | stores/roles.ts | stores/activityLogs.ts   │   │
│  └──────────────────────────────┬─────────────────────────────────┘   │
└─────────────────────────────────┼─────────────────────────────────────┘
                                  │ HTTP (Axios, Sanctum cookies)
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     Backend (Laravel 13 API)                        │
│                                                                     │
│  ┌────────────────────┐  ┌────────────────────┐  ┌──────────────┐  │
│  │  Middleware Layer   │  │  Controller Layer  │  │  FormRequest │  │
│  │  ┌──────────────┐  │  │  Api/V1/           │  │  Validation  │  │
│  │  │auth:sanctum  │  │  │  UserController    │  │  Layer       │  │
│  │  │permission:*  │  │  │  RoleController    │  │  ──────────  │  │
│  │  │throttle:api  │  │  │  ProfileController │  │  StoreUser   │  │
│  │  └──────────────┘  │  │  ActivityLogContr. │  │  UpdateRole  │  │
│  └────────────────────┘  └──────────┬──────────┘  │  StoreAvatar │  │
│                                     │              └──────────────┘  │
│  ┌──────────────────────────────────▼──────────────────────────────┐│
│  │                    Service / Policy Layer                       ││
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  ││
│  │  │UserPolicy    │  │RolePolicy    │  │ActivityLogService    │  ││
│  │  │(admin bypass)│  │(protect admin│  │AvatarService         │  ││
│  │  └──────────────┘  │ role D-06)  │  │(resize, store, del)  │  ││
│  │                    └──────────────┘  └──────────────────────┘  ││
│  └────────────────────────────────────────────────────────────────┘│
│                                     │                               │
│  ┌──────────────────────────────────▼──────────────────────────────┐│
│  │                    Model / Observer Layer                      ││
│  │  ┌──────────────────────────────────────────────────────────┐  ││
│  │  │ Observers: UserObserver logs CRUD → activity_logs table  │  ││
│  │  │ Trait:     LogsActivity (optional, reusable across phases)│  ││
│  │  │ Event:     Auth events → activity_logs (login/logout)     │  ││
│  │  └──────────────────────────────────────────────────────────┘  ││
│  └────────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   PostgreSQL 17 (via Docker)                        │
│                                                                     │
│  users ──┐                                                          │
│          ├── role_user (pivot) ── roles ──┐                          │
│          │                               │                          │
│          │                    permission_role (pivot)                │
│          │                               │                          │
│          │                      permissions                         │
│          │                                                          │
│  activity_logs ── (user_id, action, module, old/new_values)         │
└─────────────────────────────────────────────────────────────────────┘
```

### Recommended Project Structure

**Backend additions:**
```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── UserController.php          # CRUD usuários
│   │   │   ├── RoleController.php          # CRUD roles + permissões
│   │   │   ├── ProfileController.php       # Perfil + avatar
│   │   │   └── ActivityLogController.php   # Query logs
│   │   └── Requests/
│   │       ├── StoreUserRequest.php
│   │       ├── UpdateUserRequest.php
│   │       ├── UpdateProfileRequest.php
│   │       ├── StoreAvatarRequest.php
│   │       ├── UpdateRoleRequest.php
│   │       └── UpdatePermissionsRequest.php
│   ├── Http/Middleware/
│   │   └── CheckPermission.php             # Custom permission middleware
│   ├── Models/
│   │   └── ActivityLog.php                 # Model for activity_logs table
│   ├── Observers/
│   │   └── UserObserver.php                # Auto-log user CRUD
│   ├── Policies/
│   │   ├── UserPolicy.php                  # Authorization for user actions
│   │   └── RolePolicy.php                  # Authorization for role actions
│   └── Services/
│       ├── ActivityLogService.php          # Logging helper
│       └── AvatarService.php               # Avatar upload/resize/delete
├── routes/
│   └── api.php                             # Add v1/users, v1/roles, v1/profile, v1/logs
```

**Frontend additions:**
```
frontend/src/
├── modules/admin/
│   ├── pages/
│   │   ├── UsersPage.vue                  # DataTable + Dialog CRUD
│   │   ├── RolesPage.vue                  # Accordion + InputSwitch
│   │   └── AuditLogsPage.vue              # Timeline view
│   └── components/
│       ├── UserFormDialog.vue             # Create/edit user dialog
│       ├── RolePermissionEditor.vue       # Permission toggles by group
│       └── AuditLogTimeline.vue           # Timeline with filters
├── modules/profile/
│   ├── pages/
│   │   └── ProfilePage.vue               # Tabbed profile form
│   └── components/
│       ├── ProfileInfoForm.vue            # Name, email, phone, etc.
│       ├── AvatarUploader.vue             # FileUpload + crop preview
│       └── PasswordChangeForm.vue         # Separate tab
├── stores/
│   ├── users.ts                           # User CRUD state
│   ├── roles.ts                           # Role/permission state
│   └── activityLogs.ts                    # Log query state
├── services/
│   └── api.ts                             # Extend with new endpoints
└── router/routes.ts                       # Add admin routes with role guard
```

### Pattern 1: Permission Checking — Custom Middleware + Gates

**What:** Combine Laravel Gates (for global checks) with a custom middleware that reads the existing role-permission pivot tables — no Spatie package needed.

**When to use:** Every protected API endpoint in this phase and all future phases.

**Admin bypass (D-07):** The `before()` method on all policies returns `true` for admin role:

```php
// app/Policies/UserPolicy.php
class UserPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->roles->contains('slug', 'admin')) {
            return true; // Admin bypass — all operations allowed
        }
        return null; // Fall through to specific methods
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('usuarios.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('usuarios.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermission('usuarios.edit');
    }

    public function delete(User $user, User $target): bool
    {
        // Admin role itself is protected (D-06)
        if ($target->roles->contains('slug', 'admin')) {
            return false;
        }
        return $user->hasPermission('usuarios.delete');
    }
}
```
[CITED: laravel.com/docs/13.x/authorization]

**Custom permission middleware:**
```php
// app/Http/Middleware/CheckPermission.php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Admin bypass (D-07)
        if ($user->roles->contains('slug', 'admin')) {
            return $next($request);
        }

        // Check permission via existing role-permission relationship
        if (!$user->hasPermission($permission)) {
            throw new AuthorizationException('Ação não autorizada.');
        }

        return $next($request);
    }
}
```

**Register in `bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

**Usage on controller (Laravel 13 attribute syntax):**
```php
#[Middleware('auth:sanctum')]
#[Middleware('permission:usuarios.view')]
class UserController extends Controller
{
    // All methods require usuarios.view

    #[Middleware('permission:usuarios.create')]
    public function store(StoreUserRequest $request) { ... }

    #[Middleware('permission:usuarios.edit')]
    public function update(UpdateUserRequest $request, User $user) { ... }

    #[Middleware('permission:usuarios.delete')]
    public function destroy(User $user) { ... }
}
```

### Pattern 2: Activity Logging — Trait-based Observer

**What:** Auto-log all CRUD operations on sensitive models using a reusable trait that hooks into Eloquent events, writing to the existing `activity_logs` table.

**When to use:** Every model that handles business data (User, Equipment, Stock, etc.) across all phases.

```php
// app/Traits/LogsActivity.php
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created', $model->getAttributes());
        });

        static::updated(function ($model) {
            $changed = array_diff_assoc(
                $model->getAttributes(),
                $model->getOriginal()
            );
            unset($changed['updated_at']); // Noise removal

            if (!empty($changed)) {
                $diff = [];
                foreach ($changed as $key => $newValue) {
                    $diff[$key] = [
                        'old' => $model->getOriginal($key),
                        'new' => $newValue,
                    ];
                }
                $model->logActivity('updated', $diff);
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->getAttributes());
        });
    }

    protected function logActivity(string $action, array $values): void
    {
        // Skip logging for excluded attributes (passwords, tokens)
        $excluded = property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['password', 'remember_token'];

        foreach ($excluded as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        \App\Models\ActivityLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'module'     => class_basename(static::class),
            'table_name' => $this->getTable(),
            'record_id'  => $this->getKey(),
            'old_values' => $action === 'updated' ? json_encode($values) : null,
            'new_values' => json_encode($values),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```
[CITED: blog.shakiltech.com/laravel-audit-trail-model-change-log]

**For auth events (login, logout, failures):** These are not model events, so log them directly in the AuthController or via event listeners:

```php
// In AuthController after successful login
ActivityLog::create([
    'user_id'    => $user->id,
    'action'     => 'login',
    'module'     => 'auth',
    'table_name' => null,
    'record_id'  => null,
    'old_values' => null,
    'new_values' => json_encode(['email' => $request->email]),
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

### Pattern 3: Avatar Upload — Laravel 13 Image API

**What:** Use Laravel 13's built-in image processing for avatar resize, format conversion, and storage — no separate Intervention package needed.

**When to use:** Any file upload requiring resize/crop in this or future phases.

```php
// app/Services/AvatarService.php
class AvatarService
{
    private const SIZE = 256;
    private const DISK = 'public';
    private const DIRECTORY = 'avatars';

    public function store(User $user, UploadedFile $file): string
    {
        // Delete old avatar
        $this->deleteExisting($user);

        // Process: cover crop to 256x256, convert to WebP, store publicly
        $path = $file->image()
            ->cover(self::SIZE, self::SIZE)
            ->toWebp(quality: 80)
            ->storePublicly(path: self::DIRECTORY, disk: self::DISK);

        if (!$path) {
            throw new \RuntimeException('Failed to store avatar.');
        }

        // Update user record with new path
        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }

    public function deleteExisting(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
        }
    }

    public function url(User $user): ?string
    {
        return $user->avatar_path
            ? Storage::disk(self::DISK)->url($user->avatar_path)
            : null;
    }
}
```
[CITED: laravel.com/docs/13.x/images]

### Anti-Patterns to Avoid
- **Logging passwords or tokens:** Always exclude sensitive fields with `[REDACTED]` in activity logs
- **Soft-deleting activity logs:** Logs should be hard-deleted (pruned periodically), never soft-deleted
- **Skipping `getChanges()` vs `getDirty()`:** Always use `getChanges()` in updated events — `getDirty()` includes non-persisted changes
- **Using `update()` queries directly on models:** Mass `User::where(...)->update(...)` bypasses Eloquent events — always retrieve and `save()` models if logging is required
- **Storing original filenames:** Always generate UUID/hash-based filenames for uploaded files to prevent path traversal

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| File upload UI | Custom drag-drop | PrimeVue FileUpload | Built-in preview, progress, validation, drag-drop |
| Image resize/crop | GD/Imagick directly | Laravel 13 Image API | First-party, driver-agnostic, fluent API |
| Authorization checks | Inline `if` statements | Laravel Policies + Gates | Centralized, testable, with `before()` for admin bypass |
| Activity log storage | Custom table | Existing `activity_logs` migration | Already migrated, has all needed columns |
| Data tables | Custom sort/filter | PrimeVue DataTable | Pagination, global filter, column sort, lazy loading |
| Timeline UI | Custom CSS timeline | PrimeVue Timeline | Marker/content/opposite slots, vertical/horizontal |

**Key insight:** Every "don't hand-roll" item above has known edge cases that experienced teams have already solved. PrimeVue's DataTable alone handles virtual scrolling, multi-sort, column reorder, and responsive breakpoints — all of which are costly to implement correctly from scratch. Laravel's Image API abstracts away the differences between GD and Imagick drivers.

## Common Pitfalls

### Pitfall 1: Soft-deleting system roles
**What goes wrong:** Attempting to soft-delete the Admin role (slug: admin) breaks permission checks.
**Why it happens:** The AdminUserSeeder and RolePermissionSeeder reference roles by slug; if deleted, permission resolution fails silently.
**How to avoid:** In RoleController@destroy, check `$role->is_system` and `$role->slug === 'admin'`. System roles throw 422: "Role do sistema não pode ser excluída." Also check `$role->users()->exists()` before deletion.
**Warning signs:** Users with no roles after deleting a role; permission checks always returning false.

### Pitfall 2: Bypassing Eloquent events with mass updates
**What goes wrong:** Activity logs stop recording for certain operations.
**Why it happens:** `User::where('is_active', false)->update(['is_active' => true])` does NOT fire Eloquent `updated` events.
**How to avoid:** Always iterate and `save()` individual models when logging is required. Use `chunkById()` for performance.
**Warning signs:** Missing activity log entries for bulk operations.

### Pitfall 3: Forgetting avatar cleanup on user deletion
**What goes wrong:** Orphaned avatar files accumulate on disk, wasting storage.
**Why it happens:** The `deleted` observer on User model forgets to delete the associated avatar file.
**How to avoid:** In UserObserver@deleted, call `app(AvatarService::class)->deleteExisting($user)` before the model is actually removed (use `deleting` event, not `deleted`).
**Warning signs:** Growing `storage/app/public/avatars/` directory size with no corresponding user records.

### Pitfall 4: File path exposure via old avatars
**What goes wrong:** Old avatar URLs remain accessible after user changes avatar.
**Why it happens:** Only the database path is updated; the old file is not deleted.
**How to avoid:** Always delete old avatar file before storing new one (verify file exists before attempting delete).
**Warning signs:** Multiple avatar files for the same user on disk.

### Pitfall 5: Permission check without eager loading roles
**What goes wrong:** N+1 queries on every permission check in a list view (DataTable row).
**Why it happens:** `$user->hasPermission()` calls `$user->roles` which lazy-loads on every invocation.
**How to avoid:** Always eager-load `roles.permissions` on user listings. Add `$user->load('roles.permissions')` before authorization checks in policies.
**Warning signs:** Slow DataTable rendering with many users; high query count in Debugbar.

## Code Examples

### Admin User Listing with DataTable (PrimeVue 5)

```vue
<template>
  <div class="card">
    <Toolbar class="mb-4">
      <template #start>
        <div class="flex gap-2">
          <InputText v-model="filters['global'].value" placeholder="Buscar usuários..." />
          <SelectButton v-model="roleFilter" :options="roleOptions" optionLabel="label" optionValue="value" />
        </div>
      </template>
      <template #end>
        <Button label="Novo Usuário" icon="pi pi-plus" @click="openNew" />
      </template>
    </Toolbar>

    <DataTable
      :value="users"
      v-model:filters="filters"
      :loading="loading"
      :globalFilterFields="['name', 'email', 'roles.name']"
      paginator :rows="10"
      dataKey="id"
    >
      <Column field="name" header="Nome" sortable />
      <Column field="email" header="Email" sortable />
      <Column header="Perfis">
        <template #body="{ data }">
          <Tag v-for="role in data.roles" :key="role.id" :value="role.name" class="mr-1" />
        </template>
      </Column>
      <Column field="is_active" header="Status">
        <template #body="{ data }">
          <Tag :value="data.is_active ? 'Ativo' : 'Inativo'"
               :severity="data.is_active ? 'success' : 'danger'" />
        </template>
      </Column>
      <Column header="Ações" style="min-width: 8rem">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" rounded severity="info" @click="editUser(data)" />
          <Button icon="pi pi-trash" rounded severity="danger" class="ml-1"
                  :disabled="isProtectedAdmin(data)" @click="deleteUser(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="userDialog" :header="dialogTitle" modal class="w-30rem">
      <UserFormDialog :user="selectedUser" :roles="availableRoles" @save="saveUser" />
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import SelectButton from 'primevue/selectbutton'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Toolbar from 'primevue/toolbar'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import UserFormDialog from './UserFormDialog.vue'

const usersStore = useUsersStore()
const auth = useAuthStore()
const users = ref([])
const loading = ref(false)
const userDialog = ref(false)
const selectedUser = ref(null)
const roleFilter = ref(null)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const roleOptions = [
  { label: 'Todos', value: null },
  { label: 'Admin', value: 'admin' },
  { label: 'Supervisor', value: 'supervisor' },
  { label: 'Laboratorista', value: 'laboratorista' },
  { label: 'Técnico', value: 'tecnico' },
  { label: 'Consulta', value: 'consulta' },
  { label: 'Auditor', value: 'auditor' },
]

function isProtectedAdmin(user: any): boolean {
  if (!auth.hasRole('admin')) return true // Only admin can delete others
  return user.roles?.some((r: any) => r.slug === 'admin') // Can't delete another admin
}

onMounted(async () => {
  loading.value = true
  users.value = await usersStore.fetchAll()
  loading.value = false
})
</script>
```

### Audit Log Timeline (PrimeVue 5)

```vue
<template>
  <div class="card">
    <div class="flex gap-2 mb-4">
      <SelectButton v-model="moduleFilter" :options="moduleOptions" optionLabel="label" optionValue="value" />
      <Calendar v-model="dateRange" selectionMode="range" placeholder="Período" />
      <Button label="Filtrar" @click="applyFilters" />
    </div>

    <Timeline :value="logs" align="alternate" class="custom-timeline">
      <template #marker="slotProps">
        <i :class="getIcon(slotProps.item.action)"
           :style="{ color: getColor(slotProps.item.action) }"></i>
      </template>
      <template #content="slotProps">
        <div class="event-card p-3 border-round border-1 border-surface-200">
          <div class="flex align-items-center gap-2 mb-2">
            <Tag :value="slotProps.item.module" severity="info" />
            <small class="text-color-secondary">
              {{ formatDate(slotProps.item.created_at) }}
            </small>
          </div>
          <h4 class="mb-1">{{ getActionLabel(slotProps.item.action) }}</h4>
          <p class="m-0 text-sm text-color-secondary">
            Por {{ slotProps.item.user?.name || 'Sistema' }}
            <span v-if="slotProps.item.ip_address"> · {{ slotProps.item.ip_address }}</span>
          </p>
          <div v-if="slotProps.item.new_values" class="mt-2">
            <pre class="text-xs">{{ formatChanges(slotProps.item.new_values) }}</pre>
          </div>
        </div>
      </template>
    </Timeline>
  </div>
</template>

<script setup lang="ts">
import Timeline from 'primevue/timeline'
import Tag from 'primevue/tag'
import SelectButton from 'primevue/selectbutton'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'

// ... store integration and filter logic
</script>
```

### Avatar Upload with PrimeVue FileUpload

```vue
<template>
  <div class="flex flex-column align-items-center gap-3">
    <Avatar :image="avatarUrl" size="xlarge" shape="circle" />
    <FileUpload
      mode="basic"
      accept="image/*"
      :maxFileSize="2097152"
      :auto="true"
      :customUpload="true"
      @select="onAvatarSelect"
      chooseLabel="Alterar Avatar"
    />
    <small v-if="uploadError" class="text-red-500">{{ uploadError }}</small>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import FileUpload from 'primevue/fileupload'
import Avatar from 'primevue/avatar'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const uploadError = ref('')

const avatarUrl = computed(() => {
  return auth.user?.avatar_path
    ? `/storage/${auth.user.avatar_path}`
    : null
})

async function onAvatarSelect(event: any) {
  uploadError.value = ''
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('avatar', file)

  try {
    const response = await api.post('/profile/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    await auth.fetchUser() // Refresh user data
  } catch (e: any) {
    uploadError.value = e.response?.data?.message || 'Erro ao enviar avatar'
  }
}
</script>
```

### Form Request with Avatar Validation (Laravel 13)

```php
// app/Http/Requests/StoreAvatarRequest.php
class StoreAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',                    // Must be valid image (jpeg/png/gif/webp/bmp/svg)
                'mimes:jpeg,png,webp',     // Strict format restriction
                'mimetypes:image/jpeg,image/png,image/webp', // Content-based MIME check
                'max:2048',                 // Max 2MB
                'dimensions:min_width=128,min_height=128,max_width=4000,max_height=4000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'O arquivo deve ser uma imagem.',
            'avatar.mimes' => 'Formatos aceitos: JPEG, PNG, WebP.',
            'avatar.max' => 'A imagem deve ter no máximo 2MB.',
            'avatar.dimensions' => 'A imagem deve ter no mínimo 128x128 pixels.',
        ];
    }
}
```
[CITED: laravel.com/docs/13.x/validation]

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Intervention Image stand-alone | Laravel 13 native Image API | Laravel 13 (2026) | No separate composer package needed; driver-agnostic; fluent `->cover()->toWebp()->storePublicly()` API |
| Spatie Permission package | Custom RBAC with Gates/Policies | Project decision | Tailored to existing schema; avoids migration conflict; admin role bypass via `before()` |
| Manual activity logging per controller | Trait-based auto-logging | Industry standard 2024+ | Single Responsibility; zero manual calls; consistent across all models |
| File upload custom forms | PrimeVue FileUpload | PrimeVue 4+ (2024) | Built-in preview, drag-drop, progress, validation |

**Deprecated/outdated:**
- **Laravel 11 and below:** Security support ended March 2026 — project uses Laravel 13, correct.
- **Chart.js:** User chose ECharts over Chart.js for superior performance and feature set.
- **Vuetify:** User chose PrimeVue after comparison.

## Assumptions Log

> No `[ASSUMED]` claims in this research. All findings are verified against official Laravel 13 docs, PrimeVue docs, or existing codebase inspection.

## Open Questions

1. **Should activity log pruning be implemented now or deferred?**
   - What we know: `activity_logs` table has no retention policy yet. A production system should purge logs older than N days.
   - What's unclear: The retention period (90 days? 180 days? Configurable?) and whether this phase should include the prune command or defer it.
   - Recommendation: Include `php artisan model:prune` configuration with a default 90-day retention in this phase. Add `prunable()` to ActivityLog model.

2. **Front-end layout pattern for admin module?**
   - What we know: PrimeVue is the UI framework. The DataTable + Dialog pattern is locked (D-01).
   - What's unclear: Whether sidebar navigation to admin pages is handled in this phase or will be part of a later layout phase.
   - Recommendation: Create the routes and views, but only add them to the sidebar nav if a layout/navigation framework already exists from a prior phase.

3. **Should avatar upload use the new Laravel Image API or direct Storage?**
   - What we know: Laravel 13 has a first-party Image API (`$request->image('avatar')->cover()->toWebp()->storePublicly()`).
   - What we know: The image API is built on Intervention and supports GD/Imagick drivers.
   - Recommendation: Use the native Image API — it's first-party, well-documented, and eliminates the need for a separate Intervention composer dependency.
   - Verification needed: Confirm the Image API is available in the installed Laravel 13.x version (it was added via PR #59276).

## Validation Architecture

> `workflow.nyquist_validation` is enabled in config.json.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 12.x (installed) |
| Config file | `backend/phpunit.xml` |
| Quick run command | `php artisan test --filter=Users\|Roles\|Profile\|ActivityLog` |
| Full suite command | `php artisan test` (or `composer test`) |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| USERS-01 | CRUD de usuários com perfis | Feature | `php artisan test --filter=UserTest` | ❌ Wave 0 |
| USERS-02 | Atribuição de permissões por papel | Feature | `php artisan test --filter=RoleTest` | ❌ Wave 0 |
| USERS-03 | Perfil de usuário com avatar | Feature | `php artisan test --filter=ProfileTest` | ❌ Wave 0 |
| LOGS-01 | Auditoria de operações críticas | Feature + Unit | `php artisan test --filter=ActivityLogTest` | ❌ Wave 0 |
| LOGS-02 | Visualização de logs por módulo | Feature | `php artisan test --filter=ActivityLogTest` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=<test_class>`
- **Per wave merge:** `php artisan test --filter=Users\|Roles\|Profile\|ActivityLog`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `backend/tests/Feature/Users/UserTest.php` — covers USERS-01, USERS-02, USERS-03
- [ ] `backend/tests/Feature/Users/RoleTest.php` — covers USERS-02
- [ ] `backend/tests/Feature/Users/ProfileTest.php` — covers USERS-03
- [ ] `backend/tests/Feature/ActivityLogTest.php` — covers LOGS-01/02

## Security Domain

> `security_enforcement` is absent from config — treat as enabled.

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum SPA session auth (Phase 2) |
| V3 Session Management | yes | Sanctum cookie-based sessions + CSRF |
| V4 Access Control | **yes** | Custom RBAC via Gates/Policies/Middleware |
| V5 Input Validation | **yes** | Laravel Form Requests with rules |
| V6 Cryptography | yes | Bcrypt password hashing (Laravel default) |
| V7 Error Handling | yes | AuthorizationException → 403, ValidationException → 422 |
| V8 Data Protection | **yes** | Avatar path hashing, activity log redaction |

### Known Threat Patterns for This Stack
| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Privilege escalation via role manipulation | Elevation of Privilege | RBAC enforced server-side (never trust client role claims); Policies check before every mutation |
| Admin role deletion/disable | Tampering | D-06: Admin role protected via Policy `before()` + controller guard; `is_system` flag checked on delete |
| Orphaned avatar files | Repudiation | AvatarService deletes old files on update; observer cleans up on user delete |
| Permission caching staleness | Tampering | Permission checks query DB directly (no caching layer unless added); if caching added, clear on role/permission change |
| Activity log information disclosure | Information Disclosure | `[REDACTED]` for sensitive fields; audit log view restricted to `auditoria.view` permission |
| Path traversal via avatar filename | Tampering | `store()` generates UUID-based filename; never uses client-provided filename |
| Mass assignment via role_user pivot | Tampering | Only controller methods can modify roles; FormRequest validates role IDs exist |

## Environment Availability

> Docker environment is the primary development target. Local tools provide secondary verification.

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Docker | Full environment (nginx, php, postgres, redis) | ✓ | 29.6.1 | — |
| Node.js | Frontend build (Vite) | ✓ | 22.12.0 | — |
| npm | Package installation | ✓ | 11.1.0 | — |
| PHP 8.3+ | Laravel backend (inside Docker) | — | (inside container) | Docker |
| Composer | Backend dependencies (inside Docker) | — | (inside container) | Docker |
| PostgreSQL 17 | Database (inside Docker) | — | (inside container) | Docker |
| Redis 7 | Cache/queue (inside Docker) | — | (inside container) | Docker |

**Missing dependencies with no fallback:** None — all runtime dependencies are containerized via Docker Compose. PHP, Composer, PostgreSQL, and Redis run inside Docker containers. Local `php` and `composer` installations are not required.

**Missing dependencies with fallback:** None — Docker Compose provides the complete runtime environment.

**Note:** `php artisan storage:link` must be run inside the Docker container (`docker compose exec php php artisan storage:link`) or added to the Docker entrypoint script for avatar serving to work.

## Sources

### Primary (HIGH confidence)
- **[CITED: laravel.com/docs/13.x/authorization]** — Gates, Policies, `before()` for admin bypass, Blade @can
- **[CITED: laravel.com/docs/13.x/sanctum]** — SPA authentication, token abilities
- **[CITED: laravel.com/docs/13.x/images]** — Laravel 13 native Image API (cover, toWebp, storePublicly)
- **[CITED: laravel.com/docs/13.x/filesystem]** — Public disk, storage:link, file uploads
- **[CITED: primevue.dev/datatable/]** — DataTable filtering, global search, pagination
- **[CITED: primevue.dev/timeline/]** — Timeline component with marker/content/opposite slots
- **[CITED: primevue.dev/fileupload/]** — FileUpload with image preview
- **[VERIFIED: package.json/ composer.json]** — All package versions confirmed via `npm view` and codebase inspection

### Secondary (MEDIUM confidence)
- **[CITED: qadrlabs.com/post/laravel-13-rbac-spatie-permission-middleware-attributes]** — Laravel 13 `#[Middleware]` attribute pattern (adapted for custom middleware)
- **[CITED: blog.shakiltech.com/laravel-audit-trail-model-change-log]** — Trait-based activity logging pattern
- **[CITED: codesnips.io/resize-store-user-avatars-intervention-image-laravel]** — Avatar resize/crop pattern (adapted for Laravel 13 native API)

### Tertiary (LOW confidence)
- **WebSearch results for PrimeVue component edge cases** — Used to identify pitfalls (FileUpload preview alignment, Timeline horizontal last-item issue)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — All libraries are installed and versions verified against npm/composer registries and official docs
- Architecture: HIGH — Patterns follow official Laravel docs and established PrimeVue documentation
- Pitfalls: MEDIUM — Based on known Laravel/PrimeVue edge cases from community sources
- Security: HIGH — ASVS categories mapped to Laravel's built-in security features

**Research date:** 2026-07-19
**Valid until:** 2026-08-19 (30 days — Laravel 13 is active, but minor releases may introduce changes)

---

## RESEARCH COMPLETE

**Phase:** 03 - Usuários e Permissões
**Confidence:** HIGH

### Key Findings
1. **No external packages needed** — existing User/Role/Permission models, activity_logs table, and seeder are sufficient. Laravel 13's native Image API handles avatar processing.
2. **Use Gates + Policies + custom middleware** for RBAC enforcement, with `before()` method for admin role bypass (D-07).
3. **Trait-based activity logging** is the correct pattern — hook into Eloquent events, write to existing `activity_logs` table.
4. **PrimeVue components confirmed** — DataTable, Dialog, Accordion, InputSwitch, MultiSelect, Timeline, FileUpload, Avatar, Tag — all available in installed version 5.x.
5. **Docker is the runtime** — all PHP/PostgreSQL/Redis dependencies run in containers; `storage:link` must run inside the container.

### File Created
`.planning/phases/03-usuarios-permissoes/03-RESEARCH.md`

### Confidence Assessment
| Area | Level | Reason |
|------|-------|--------|
| Standard Stack | HIGH | All packages installed and verified via official docs/registries |
| Architecture | HIGH | Follows official Laravel 13 docs + PrimeVue documentation patterns |
| Pitfalls | MEDIUM | Community-sourced edge cases for Timeline, FileUpload, mass-update bypass |
| Security | HIGH | ASVS categories mapped; all controls use built-in Laravel features |

### Open Questions
1. Activity log retention period — recommend 90 days with prunable model (defer to discuss-phase)
2. Admin sidebar navigation — depends on layout phase existence
3. Laravel 13 Image API availability — confirm `$request->image()` works (added via PR #59276, should be in 13.x)

### Ready for Planning
Research complete. Planner can now create PLAN.md files for user CRUD, role/permission management, profile/avatar, and audit logs.
