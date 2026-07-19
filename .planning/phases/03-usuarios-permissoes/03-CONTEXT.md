# Phase 3: Usuários e Permissões - Context

**Gathered:** 2026-07-19
**Status:** Ready for planning

<domain>
## Phase Boundary

CRUD completo de usuários com atribuição de perfis (roles), gerenciamento de permissões por papel, perfil do usuário com avatar, e logs de auditoria com timeline visual.

**Requisitos cobertos:**
- USERS-01: CRUD de usuários com perfis (Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor)
- USERS-02: Atribuição de permissões por papel
- USERS-03: Perfil de usuário com avatar e dados pessoais
- LOGS-01: Auditoria de todas as operações críticas + autenticação
- LOGS-02: Visualização de logs por módulo com timeline visual

</domain>

<decisions>
## Implementation Decisions

### 1. CRUD UI Pattern (USERS-01)
- **D-01:** Listagem com PrimeVue DataTable + criação/edição via Dialog (modal)
- **D-02:** Filtros: campo de busca textual + dropdown de role + status (ativo/inativo)

### 2. Role & Permission UX (USERS-02)
- **D-03:** Permissões gerenciadas via lista de toggle switches, agrupadas por categoria com acordeão (PrimeVue Accordion)
- **D-04:** Atribuição de roles via MultiSelect simples — admin pode atribuir múltiplos perfis a um usuário
- **D-05:** Permissões são vinculadas a roles, não diretamente a usuários (RBAC)

### 3. Proteção de Roles
- **D-06:** Role Admin (`slug: admin`) é protegida — não pode ser editada nem excluída via API
- **D-07:** Usuários com role Admin têm bypass total em todas as verificações de permissão (middleware/super_admin gate)
- **D-08:** Demais roles (incluindo Supervisor com `is_system=true`) são editáveis e excluíveis

### 4. Perfil do Usuário (USERS-03)
- **D-09:** O usuário pode editar: nome, email, telefone, cargo, avatar, departamento, assinatura digital
- **D-10:** Troca de senha em aba separada no formulário de perfil
- **D-11:** Avatares armazenados em storage local (`backend/storage/app/public/avatars/` com symlink)

### 5. Logs de Auditoria (LOGS-01/02)
- **D-12:** Escopo: operações críticas (criação, edição, exclusão em módulos sensíveis) + eventos de autenticação (login, logout, falhas, verificação email, reset senha)
- **D-13:** Interface: timeline visual cronológica com ícones por tipo de operação, agrupada por data, com filtros por módulo, usuário e período
- **D-14:** ActivityLog model já existe na migration `0001_01_01_000004_create_activity_logs_table.php`

### Agent's Discretion
- Ordem de implementação dos sub-módulos (Users API → Roles API → Profile → Logs)
- Nomes específicos de rotas e componentes seguindo convenções existentes

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — USERS-01, USERS-02, USERS-03, LOGS-01, LOGS-02
- `.planning/PROJECT.md` — Stack, key decisions, UUID, Sanctum

### Existing Code
- `backend/app/Models/User.php` — Model com roles relationship, casts
- `backend/app/Models/Role.php` — Model com is_system, permissions relationship
- `backend/app/Models/Permission.php` — Model com group field
- `backend/database/migrations/0001_01_01_000003_create_roles_and_permissions_tables.php` — Pivot tables
- `backend/database/migrations/0001_01_01_000004_create_activity_logs_table.php` — Activity logs table
- `backend/database/migrations/0001_01_01_000000_create_users_table.php` — Users table
- `backend/database/seeders/RolePermissionSeeder.php` — 6 roles, 32 permissions
- `backend/app/Http/Controllers/Api/V1/AuthController.php` — Existing controller pattern
- `backend/routes/api.php` — Route conventions (prefix v1, Sanctum middleware)
- `frontend/src/stores/auth.ts` — Auth store with hasRole, hasPermission

### Prior Phase Context
- `.planning/phases/02-autenticacao/02-CONTEXT.md` — Auth decisions carried forward

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **User, Role, Permission models** — Already exist with relationships, UUIDs, timestamps
- **RolePermissionSeeder** — Seeds 6 roles and 32 permissions, idempotent
- **AuthStore** — `hasRole()`, `hasPermission()` methods already implemented
- **Router guards** — `meta.roles` middleware already configured
- **PrimeVue components** — DataTable, Dialog, MultiSelect, Accordion, InputSwitch, InputText, Avatar
- **ActivityLog migration** — Table structure ready for use

### Established Patterns
- **Backend:** Controllers in `Api\V1`, Form Requests for validation, JSON responses
- **Frontend:** Pinia stores in `stores/`, composables in `composables/`, services in `services/`
- **Routes:** `api.php` prefix `/api/v1`, Sanctum middleware for protected routes
- **API responses:** Consistent JSON format with `user`, `message` keys

### Integration Points
- `/api/v1/users` — New CRUD routes for user management
- `/api/v1/roles` — New CRUD routes for role/permission management
- `/api/v1/profile` — Profile and avatar endpoints
- `/api/v1/logs` — Activity log query endpoints
- `frontend/src/modules/admin/` — New module directory for admin views
- Router: Add admin routes with `meta: { roles: ['admin'] }` guard

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches following established PrimeVue + Laravel patterns.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 03-Usuarios e Permissões*
*Context gathered: 2026-07-19*
