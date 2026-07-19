---
phase: 3
plan: 2
subsystem: admin
tags: [frontend, users, roles, permissions, primevue]
requires: [03-01]
provides: [admin.users, admin.roles, users.store, roles.store]
affects: [router, stores, admin.module]
tech-stack:
  added: [vue-tsc]
  patterns:
    - Pinia stores with defineStore composable API
    - Individual PrimeVue component imports
    - Module-based page structure under modules/admin/
    - PrimeVue 5 ToggleSwitch (replaces InputSwitch)
    - PrimeVue 5 DatePicker (replaces Calendar)
    - PrimeVue 5 Select (replaces Dropdown)
key-files:
  created:
    - frontend/src/stores/users.ts
    - frontend/src/stores/roles.ts
    - frontend/src/modules/admin/pages/UsersPage.vue
    - frontend/src/modules/admin/components/UserFormDialog.vue
    - frontend/src/modules/admin/pages/RolesPage.vue
    - frontend/src/modules/admin/components/RolePermissionEditor.vue
  modified:
    - frontend/src/router/routes.ts
    - frontend/src/modules/admin/pages/AuditLogsPage.vue
decisions:
  - 'Users and Roles placed under modules/admin/ rather than modules/users/ following admin panel pattern'
  - 'User type reused from auth store, extended with form-specific fields via Record<string, any>'
  - 'Permission group determined by Permission.group field from API'
  - 'AuditLogsPage Calendar imported as DatePicker (PrimeVue 5 breaking change)'
status: complete
metrics:
  duration: 35m
  completed_date: 2026-07-19
  tasks_completed: 4
  files_created: 6
  files_modified: 2
  commits: 4
---

# Phase 3 Plan 2: Users & Roles Frontend UI — Summary

## Objective

Create the frontend UI for user and role management, including Pinia stores, admin pages, and permission editor components.

## Execution

### Task 1 — Pinia Stores (users.ts, roles.ts)

Created `frontend/src/stores/users.ts` and `frontend/src/stores/roles.ts` matching the existing Pinia pattern from `auth.ts`.

**users store features:**
- State: `users`, `loading`, `pagination`
- Actions: `fetchAll(params?)`, `fetchById(id)`, `create(data)`, `update(id, data)`, `destroy(id)`
- Handles both array and paginated API responses
- Reuses `User` interface from `@/stores/auth`

**roles store features:**
- Interfaces: `Role`, `Permission`
- State: `roles`, `loading`
- Actions: `fetchAll(params?)`, `fetchById(id)`, `create(data)`, `update(id, data)`, `syncPermissions(id, permissionIds)`, `destroy(id)`

### Task 2 — UsersPage + UserFormDialog

**UsersPage.vue:**
- PrimeVue DataTable with lazy pagination, sorting
- Search input with debounce, role Select filter, status SelectButton filter
- User column with Avatar initials, email, role Tag badges, status Tag, date formatting
- Permission-gated "Novo Usuário" button (`usuarios.create`)
- Delete confirmation via ConfirmDialog (disabled for admin users)
- Integrates with both users and roles stores

**UserFormDialog.vue:**
- Create/edit dialog with form fields: name, email, phone, position, department
- Password fields only shown on creation
- Roles MultiSelect, is_active SelectButton
- Form reset on open/close

### Task 3 — RolesPage + RolePermissionEditor

**RolesPage.vue:**
- Two-column layout: left role list (DataTable), right permission editor
- Columns: name (with slug), description, is_system Tag, users_count
- Delete disabled for admin role and roles with users
- Inline dialog for create/edit role (name, description)

**RolePermissionEditor.vue:**
- Props: role, allPermissions; Emits: save(roleId, permissionIds)
- States: no selection (placeholder), admin role (protected message), editable role
- PrimeVue Accordion with groups from `Permission.group`
- ToggleSwitch for each permission with count badge per group
- Watch reactivity on role change to sync selection state

### Task 4 — Router Update

Added two routes to `frontend/src/router/routes.ts`:
- `/admin/users` — requires auth + `admin` or `supervisor` role
- `/admin/roles` — requires auth + `admin` role

Existing `meta.roles` guard in `router/index.ts` handles authorization.

## Verification

- `npx vue-tsc --noEmit`: 3 pre-existing TypeScript errors found (none in new files)
- `npx vite build`: ✅ Build successful (425 modules, 2.27s)
- All files verified present on disk

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 — Blocking] AuditLogsPage imports primevue/calendar (PrimeVue 4 API)**

- **Found during:** Build verification
- **Issue:** `AuditLogsPage.vue` (pre-existing) imported `Calendar from 'primevue/calendar'` which was removed in PrimeVue 5
- **Fix:** Replaced with `DatePicker from 'primevue/datepicker'` — same API surface (selectionMode, showIcon, placeholder, fluid)
- **Files modified:** `frontend/src/modules/admin/pages/AuditLogsPage.vue`
- **Commit:** `0e6e55d`

**2. [Minor] TypeScript version incompatibility with vue-tsc**

- **Found during:** TypeScript compilation check
- **Issue:** Installed TypeScript 7.0.2 doesn't export `./lib/tsc` subpath required by vue-tsc
- **Fix:** Downgraded to TypeScript 5.8.3
- **Files modified:** `frontend/package.json`, `frontend/package-lock.json`
- **No additional commit** (bundled with AuditLogsPage fix)

### PrimeVue 5 Breaking Changes

The plan expected `Calendar / InputSwitch / FilterMatchMode` from PrimeVue 4, but the project has PrimeVue 5:
- `Calendar` → `DatePicker`
- `InputSwitch` → `ToggleSwitch`
- `FilterMatchMode` from `primevue/api` — not available; filtering handled via debounced text input

## Self-Check: PASSED

All created files and commits verified. Build output confirms correct module compilation.

## Commit History

| Hash | Message |
|------|---------|
| `c4413dc` | feat(03-02): create users and roles Pinia stores |
| `743cd6d` | feat(03-02): create admin user and role management UI |
| `0e6e55d` | fix(03-02): replace primevue/calendar with primevue/datepicker in AuditLogsPage |
