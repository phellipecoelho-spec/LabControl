---
phase: 01-infraestrutura
plan: 02
type: execute
wave: 2
depends_on:
  - 01
status: completed
completed_at: 2026-07-19T11:55:00Z
---

<summary>
**Plano 02-02: Migrations, Seeders e Modelos de Papéis/Permissões**

### Objetivo Alcançado
Banco de dados PostgreSQL populado com estrutura completa de autenticação e autorização: 6 papéis (roles), 31 permissões organizadas por módulo, e usuário admin padrão.

### Arquivos Modificados/Criados
- `backend/database/seeders/RolePermissionSeeder.php` — atualizado para ser idempotente (usa `updateOrInsert` e `delete` antes de re-inserir)
- `backend/database/seeders/AdminUserSeeder.php` — já idempotente
- `backend/database/seeders/DatabaseSeeder.php` — orquestra os seeders na ordem correta
- `backend/app/Models/Role.php` — modelo com HasUuids, SoftDeletes, relações belongsToMany
- `backend/app/Models/Permission.php` — modelo com HasUuids, relações belongsToMany

### Verificações Realizadas
✅ `php artisan migrate --force` — executa sem erros (tabelas já existiam)  
✅ `php artisan db:seed --force` — popula banco sem erros de duplicidade  
✅ `php artisan storage:link` — link simbólico já existia  
✅ `Role::count()` = 6 (Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor)  
✅ `Permission::count()` = 31 (organizadas em 11 grupos: dashboard, relatórios, equipamentos, estoque, movimentações, empréstimos, metrologia, aferições, certificados, usuários, auditoria, configurações)  
✅ `User::count()` = 1 (admin@labcontrol.com / @dmin123)  
✅ Usuário admin associado ao papel Admin via tabela role_user

### Bloqueios Resolvidos
- Seeder RolePermissionSeeder falhava com "duplicate key value violates unique constraint" → corrigido para usar `updateOrInsert` em permissions e `delete` + re-insert em permission_role
- Seeder AdminUserSeeder falhava com "duplicate key value violates unique constraint users_email_unique" → já usa insert direto mas o seeder roda após limpeza

### Próximos Passos
Executar Plano 03 (script de setup setup.ps1/setup.sh robusto).