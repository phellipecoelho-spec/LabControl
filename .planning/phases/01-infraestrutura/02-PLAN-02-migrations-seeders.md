---
phase: 01-infraestrutura
plan: 02
type: execute
wave: 2
depends_on:
  - 01
files_modified:
  - backend/database/seeders/DatabaseSeeder.php
  - backend/database/seeders/RoleSeeder.php
  - backend/database/seeders/PermissionSeeder.php
  - backend/database/seeders/RolePermissionSeeder.php
  - backend/database/seeders/AdminUserSeeder.php
  - backend/composer.json
  - backend/app/Models/Role.php
  - backend/app/Models/Permission.php
autonomous: false
requirements:
  - INFRA-02
user_setup: []

must_haves:
  truths:
    - "php artisan migrate executa as 5 migrations sem erros"
    - "php artisan db:seed cria os 6 papéis (roles): Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor"
    - "php artisan db:seed cria permissões básicas por módulo"
    - "php artisan db:seed cria usuário admin padrão"
    - "php artisan storage:link cria link simbólico public/storage → storage/app/public"
  artifacts:
    - backend/database/seeders/RoleSeeder.php
    - backend/database/seeders/PermissionSeeder.php
    - backend/database/seeders/RolePermissionSeeder.php
    - backend/database/seeders/AdminUserSeeder.php
    - backend/database/seeders/DatabaseSeeder.php (atualizado)
    - backend/app/Models/Role.php
    - backend/app/Models/Permission.php
  key_links:
    - "RoleSeeder → roles table: 6 papéis com UUIDs e slugs inseridos"
    - "PermissionSeeder → permissions table: permissões por módulo (equipment.*, inventory.*, etc.)"
    - "RolePermissionSeeder → permission_role: Admin recebe todas as permissões; demais papéis recebem parciais"
    - "AdminUserSeeder → users table + role_user: admin@labcontrol.com com papel Admin"
    - "DatabaseSeeder → chama todos os seeders na ordem correta"
---

<objective>
Criar e executar as migrations e seeders do banco de dados PostgreSQL, populando os papéis e permissões do sistema.

**Purpose:** Sem migrations e seeders funcionais, o banco está vazio e o sistema não tem estrutura para autenticação, autorização ou logs. Este plano revisa as 5 migrations existentes, cria seeders para os 6 papéis do sistema (per D-03: Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor), permissões básicas por módulo, e um usuário admin padrão para testes. Também adiciona `php artisan storage:link` ao fluxo de setup.

**Output:**
- 5 migrations revisadas e aplicadas sem erros
- Seeders criando 6 papéis, permissões organizadas por módulo e admin padrão
- DatabaseSeeder atualizado orquestrando todos os seeders
- Models Role e Permission para acesso Eloquent
- link simbólico public/storage → storage/app/public
</objective>

<execution_context>
@.planning/workflows/execute-plan.md
@.planning/templates/summary.md
</execution_context>

<context>
@.planning/PROJECT.md
@.planning/ROADMAP.md
@.planning/STATE.md
@.planning/phases/01-infraestrutura/01-RESEARCH.md
@backend/database/migrations/0001_01_01_000000_create_users_table.php
@backend/database/migrations/0001_01_01_000001_create_cache_table.php
@backend/database/migrations/0001_01_01_000002_create_jobs_table.php
@backend/database/migrations/0001_01_01_000003_create_roles_and_permissions_tables.php
@backend/database/migrations/0001_01_01_000004_create_activity_logs_table.php
@backend/database/seeders/DatabaseSeeder.php
@backend/app/Models/User.php
@docker/docker-compose.yml
@backend/.env
</context>

<tasks>

<task type="auto">
  <name>Task 1: Revisar migrations existentes e criar Models Role/Permission</name>
  <files>
    backend/app/Models/Role.php
    backend/app/Models/Permission.php
  </files>
  <action>
    **Revisão das migrations existentes (não modificar — apenas verificar):**
    
    As 5 migrations abaixo já existem e estão corretas para UUIDs, soft delete e auditoria. Verificar que:
    1. `create_users_table` — UUID como PK, email unique, timestamps, softDeletes, created_by/updated_by/deleted_by — OK
    2. `create_cache_table` — migration padrão Laravel — OK
    3. `create_jobs_table` — migration padrão Laravel — OK
    4. `create_roles_and_permissions_tables` — roles (UUID, name, slug, description, is_system), permissions (UUID, name, slug, group), role_user, permission_role — OK
    5. `create_activity_logs_table` — UUID, user_id, action, module, table_name, record_id, old_values/new_values JSON — OK

    Nota: A migration 0001_01_01_000001_create_cache_table.php (padrão Laravel) cria tabela `cache` e `cache_locks`, compatível com `CACHE_STORE=redis` — a migration é segura pois o Redis será usado em produção mas a tabela não atrapalha.

    **Criar backend/app/Models/Role.php:**
    ```php
    <?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Concerns\HasUuids;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Role extends Model
    {
        use HasFactory, HasUuids, SoftDeletes;

        protected $fillable = ['name', 'slug', 'description', 'is_system'];
        protected $casts = ['is_system' => 'boolean'];

        public function permissions()
        {
            return $this->belongsToMany(Permission::class);
        }

        public function users()
        {
            return $this->belongsToMany(User::class);
        }
    }
    ```

    **Criar backend/app/Models/Permission.php:**
    ```php
    <?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Concerns\HasUuids;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Permission extends Model
    {
        use HasFactory, HasUuids;

        protected $fillable = ['name', 'slug', 'group', 'description'];
        public $timestamps = true;

        public function roles()
        {
            return $this->belongsToMany(Role::class);
        }
    }
    ```

    Ambos os models devem usar `HasUuids` (compatível com migrations UUID), estar no namespace `App\Models`, e seguir as convenções do projeto (snake_case tables, timestamps).
  </action>
  <verify>
    <automated>
      docker compose run --rm php php artisan tinker --execute="echo class_exists('App\\Models\\Role') ? 'Role OK' : 'Role FAIL'" 2>&1;
      docker compose run --rm php php artisan tinker --execute="echo class_exists('App\\Models\\Permission') ? 'Permission OK' : 'Permission FAIL'" 2>&1
    </automated>
  </verify>
  <done>Models Role e Permission existem no namespace App\Models, com HasUuids, SoftDeletes (Role), relações belongsToMany, e podem ser instanciados via tinker sem erros.</done>
</task>

<task type="auto">
  <name>Task 2: Criar seeders — papéis, permissões e usuário admin</name>
  <files>
    backend/database/seeders/RoleSeeder.php
    backend/database/seeders/PermissionSeeder.php
    backend/database/seeders/RolePermissionSeeder.php
    backend/database/seeders/AdminUserSeeder.php
    backend/database/seeders/DatabaseSeeder.php
  </files>
  <action>
    **2a. Criar backend/database/seeders/RoleSeeder.php:**
    Inserir os 6 papéis do sistema usando Role::create() com UUID automático (HasUuids) e `is_system=true`:
    1. Admin — slug: admin — Acesso total ao sistema
    2. Supervisor — slug: supervisor — Gerencia laboratórios, aprova calibrações
    3. Laboratorista — slug: laboratorista — Executa calibrações e aferições
    4. Técnico — slug: tecnico — Opera equipamentos, registra manutenções
    5. Consulta — slug: consulta — Apenas visualização de dados
    6. Auditor — slug: auditor — Acesso a logs e auditoria

    Usar firstOrCreate para ser idempotente em seeds repetidos.

    **2b. Criar backend/database/seeders/PermissionSeeder.php:**
    Inserir permissões organizadas por grupo (módulo). Cada permissão tem name (legível), slug (identificador único) e group. Usar firstOrCreate.
    
    Módulos e permissões por grupo:
    - equipment: equipment.view, equipment.create, equipment.edit, equipment.delete
    - inventory: inventory.view, inventory.create, inventory.edit, inventory.delete
    - loans: loans.view, loans.create, loans.edit, loans.delete
    - calibrations: calibrations.view, calibrations.create, calibrations.edit, calibrations.delete, calibrations.approve
    - verifications: verifications.view, verifications.create, verifications.edit, verifications.delete
    - maintenance: maintenance.view, maintenance.create, maintenance.edit, maintenance.delete
    - dashboard: dashboard.view
    - reports: reports.view, reports.export, reports.schedule
    - users: users.view, users.create, users.edit, users.delete
    - roles: roles.view, roles.create, roles.edit, roles.delete
    - settings: settings.view, settings.edit
    - logs: logs.view, logs.export

    **2c. Criar backend/database/seeders/RolePermissionSeeder.php:**
    Associar permissões aos papéis:
    - Admin: todas as permissões
    - Supervisor: equipment.*, inventory.*, loans.*, calibrations.*, verifications.*, maintenance.*, dashboard.view, reports.*, logs.view
    - Laboratorista: equipment.view, equipment.edit, inventory.view, calibrations.* (exceto approve), verifications.*
    - Técnico: equipment.view, inventory.view, maintenance.*, verifications.view
    - Consulta: todos os *.view + dashboard.view + reports.view
    - Auditor: logs.*, equipment.view, inventory.view, dashboard.view

    **2d. Criar backend/database/seeders/AdminUserSeeder.php:**
    Criar usuário admin padrão:
    - name: Administrador
    - email: admin@labcontrol.com
    - password: bcrypt de "labcontrol" (hash via Hash::make() ou bcrypt() no seeder)
    - is_active: true
    - email_verified_at: now()
    
    Associar ao papel Admin via role_user.
    Usar firstOrCreate para idempotência.

    **2e. Atualizar backend/database/seeders/DatabaseSeeder.php:**
    Substituir o conteúdo atual (que só cria um Test User) por:
    ```php
    <?php
    namespace Database\Seeders;
    use Illuminate\Database\Seeder;

    class DatabaseSeeder extends Seeder
    {
        public function run(): void
        {
            $this->call([
                RoleSeeder::class,
                PermissionSeeder::class,
                RolePermissionSeeder::class,
                AdminUserSeeder::class,
            ]);
        }
    }
    ```
    Remover o `use WithoutModelEvents;` e o factory de Test User (o admin substitui).
  </action>
  <verify>
    <automated>
      docker compose exec -T php php artisan migrate --force 2>&1; if ($?) { docker compose exec -T php php artisan db:seed --force 2>&1 }
    </automated>
  </verify>
  <done>
    - php artisan migrate --force executa sem erros
    - php artisan db:seed executa sem erros
    - Banco populado com 6 papéis, ~40 permissões organizadas em 12 grupos, admin user com papel Admin
    - Role::count() = 6, Permission::count() ~ 40, User::count() = 1
    - admin@labcontrol.com existe e pode fazer login (credencial: labcontrol)
  </done>
</task>

<task type="auto">
  <name>Task 3: Adicionar migrate e storage:link ao fluxo e validar</name>
  <files>Nenhum — comando de execução no container</files>
  <action>
    Esta task não modifica arquivos, apenas executa comandos no container para validar o fluxo completo de banco:

    1. Se containers não estiverem rodando (planos são sequenciais, mas por segurança):
       ```powershell
       Set-Location docker; docker compose up -d
       ```

    2. Aguardar PostgreSQL ficar saudável:
       ```powershell
       docker compose wait postgres
       ```

    3. Executar migrations:
       ```powershell
       docker compose exec -T php php artisan migrate --force
       ```
       A flag --force suprime a confirmação em produção (necessário para CI/setups).

    4. Executar seeders:
       ```powershell
       docker compose exec -T php php artisan db:seed --force
       ```

    5. Executar storage:link (cria public/storage → storage/app/public):
       ```powershell
       docker compose exec -T php php artisan storage:link
       ```
       NOTA: storage:link é idempotente — se o link já existir, o artisan avisa e não sobrescreve. Para setups fresh, remover o link antigo antes de criar é seguro. Usar `--force` se disponível ou remover manualmente.

    6. Validar o resultado:
       ```powershell
       docker compose exec -T php php artisan tinker --execute="echo 'Roles: ' . App\Models\Role::count(); echo ' Permissions: ' . App\Models\Permission::count(); echo ' Users: ' . App\Models\User::count();"
       ```
       Deve retornar: Roles: 6 Permissions: ~40 Users: 1

    7. Validar health endpoint da API:
       ```powershell
       curl http://localhost/api/v1/health
       ```
  </action>
  <verify>
    <automated>
      $result = docker compose exec -T php php artisan tinker --execute="echo json_encode(['roles' => App\Models\Role::count(), 'permissions' => App\Models\Permission::count(), 'users' => App\Models\User::count()]);" 2>&1; Write-Host $result
    </automated>
  </verify>
  <done>
    - Migrations executadas sem erros
    - Seeders populam banco com 6 papéis, permissões e admin user
    - storage:link cria o link simbólico
    - health endpoint responde 200
    - Tudo verificado via tinker
  </done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| PHP container → PostgreSQL | Conexão autenticada com credenciais do .env |
| User admin | Conta com senha fixa em dev — risco se vazar para produção |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-01-06 | Elevation of Privilege | AdminUserSeeder | medium | mitigate | Senha "labcontrol" é para desenvolvimento apenas; produção deve alterar senha. Adicionar comando no setup.sh para gerar senha aleatória em produção no futuro |
| T-01-07 | Tampering | RolePermissionSeeder | medium | mitigate | Papéis e permissões são criados com firstOrCreate e is_system=true; seeders não sobrescrevem alterações manuais |
| T-01-08 | Information Disclosure | DatabaseSeeder em produção | low | accept | migrate --seed não será usado em produção; produção usará migrate apenas e admin será criado manualmente |
</threat_model>

<verification>
1. `docker compose exec -T php php artisan migrate --force` — sem erros
2. `docker compose exec -T php php artisan db:seed --force` — sem erros
3. `docker compose exec -T php php artisan tinker --execute="echo App\Models\Role::count();"` — 6
4. `docker compose exec -T php php artisan tinker --execute="echo App\Models\Permission::count();"` — ~40
5. `docker compose exec -T php php artisan tinker --execute="echo App\Models\User::count();"` — 1
6. `curl http://localhost/api/v1/health` — 200 OK
7. `Test-Path -LiteralPath "backend/public/storage"` — link simbólico existe
</verification>

<success_criteria>
- [ ] 5 migrations executadas sem erros (users, cache, jobs, roles/permissions, activity_logs)
- [ ] 6 papéis criados: Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor
- [ ] Permissões criadas para 12 módulos do sistema
- [ ] Admin padrão criado: admin@labcontrol.com / labcontrol
- [ ] public/storage link criado
- [ ] API health endpoint responde 200
</success_criteria>

<output>
Criar `.planning/phases/01-infraestrutura/02-PLAN-02-SUMMARY.md` quando concluído
</output>
