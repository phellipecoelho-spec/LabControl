## 03-01-SUMMARY: Backend User & Role Management API

**Execution:** Executed inline (no subagent) — 4 tasks completed

### What was delivered

1. **Migration & Middleware:**
   - `2026_07_19_000001_add_avatar_path_to_users_table.php` — adds avatar_path, phone, position, department, signature to users table
   - `app/Http/Middleware/CheckPermission.php` — middleware with admin bypass and User::hasPermission() check
   - `User::hasPermission(string $slug): bool` added to User model

2. **4 Form Requests:**
   - `StoreUserRequest.php` — name, email, password+confirmation, roles, phone, position, department
   - `UpdateUserRequest.php` — same fields except password nullable/conditional, plus is_active
   - `UpdateRoleRequest.php` — name, description
   - `UpdatePermissionsRequest.php` — permissions array with exists validation
   - All with Portuguese error messages

3. **2 Policies:**
   - `UserPolicy.php` — admin bypass via before(), CRUD methods checking usuarios.{action} permissions, admin target protection on delete
   - `RolePolicy.php` — admin bypass, admin role protection on update/delete, blocks delete if role has users

4. **2 Controllers + Routes:**
   - `UserController.php` — index (paginated, search, role/status filters), show, store, update, destroy (soft-delete)
   - `RoleController.php` — index, show, store, update, destroy (protected admin), syncPermissions
   - 11 API routes registered under api/v1 (5 users + 6 roles)
   - `permission` middleware alias registered in bootstrap/app.php

### Verification
- ✅ Migration ran successfully
- ✅ All 11 routes registered
- ✅ No PHP syntax errors in any new file
