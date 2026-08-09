# Phase 15: Correções de Funcionamento - Research

**Researched:** 2026-08-09
**Domain:** Laravel 13 controller middleware/RBAC, seeder idempotency, backend test suite repair
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

> No phase-level CONTEXT.md exists for Phase 15; these constraints come from the project root
> CONTEXT.md (platform direction) and PLANNER.md (the user's action plan for this milestone).

### Locked Decisions
- **Stack (locked):** Vue 3 + Vite + TypeScript + PrimeVue + Pinia + Vue Router; Laravel API REST com
  Sanctum; PostgreSQL; Redis; Apache ECharts; Docker Compose; Git + GitHub; OpenAPI/Swagger.
- **Arquitetura:** Tudo via API — nunca acesso direto ao banco. Módulos independentes
  (equipamentos, estoque, metrologia, qualidade, documentos, usuários, dashboards), ativáveis/desativáveis.
- **Base preparada para multiempresa e multilaboratório.**
- **Tema escuro, visual moderno** (referências: Power BI, Azure, Notion, GitHub, Linear, ClickUp).
- **Execução local e online:** mesma arquitetura, apenas configurações de ambiente diferentes.
- **"Nenhuma entrega será um 'exemplo de código'. Todas serão código de produção, documentado,
  testável e versionado."**

### the agent's Discretion
- PLANNER.md Pilar 2: remoção do aviso "Invalid PrimeUI License" (identificar origem e remover/silenciar).
- PLANNER.md Pilar 2: posicionamento de botões de ação no cabeçalho das tabelas; revisão de modais;
  ajuste de dimensionamento dos gráficos do Dashboard.
- PLANNER.md Pilar 3: remoção de arquivos obsoletos (ex.: `debug_login.php`, `tmp_test.php`), dead code.
- PLANNER.md Pilar 4: README.md profissional com badges, stack, guias de instalação e capturas de tela.
- PLANNER.md Pergunta aberta 1: modo de silenciamento da licença PrimeUI (regra CSS vs. configuração).
- PLANNER.md Pergunta aberta 2: exclusão de equipamento com empréstimo ativo (422 descritivo vs. soft delete).

### Deferred Ideas (OUT OF SCOPE)
- Aplicativo mobile nativo (PWA suficiente para v1.x; Capacitor no futuro).
- Multiempresa/multilaboratório (TENANT-01/02), IoT (IOT-01), faturamento/NFe, CRM, chat interno,
  migração de dados legados, testes de carga em produção — fora do roadmap v1.1.
</user_constraints>

## Summary

Phase 15 targets BUG-01 (seeders must create admin/roles/permissions in a clean environment) and
BUG-02 (bugs found in the verification scan must be fixed and validated). This research ran the full
backend test suite inside the running Docker environment (33 failed / 124 passed), probed the live API
with zero-permission users, dumped the actual controller middleware Laravel 13 resolves for every
route, and read the vendor source of the middleware pipeline. The result is a precise,
empirically-verified root-cause map of the phase's bugs.

**The dominant root cause of BUG-02 is a systematic middleware misconfiguration.** Laravel 13
installed here is `v13.20.0` (composer: `laravel/framework: ^13.8`), which removed the legacy
array-format controller middleware API (`['middleware' => ..., 'only' => ...]` and the
`'options' => ['only' => ...]` variant). Two distinct failure modes were proven with live probes:

1. **Silent RBAC bypass (CRITICAL):** 14 controllers declare `public static function middleware()`
   but do NOT `implements HasMiddleware`. Laravel 13 only gathers controller middleware from classes
   implementing `HasMiddleware` (or legacy `getMiddleware`). The method is dead code —
   `controllerMiddleware()` returns `[]`. Routes wrapped in `auth:sanctum` at the route-group level
   still enforce authentication, but **every `permission:` check is silently dropped**. A zero-permission
   user received `200` on `GET /api/v1/equipments` and reached validation on `POST /api/v1/equipments`
   (422, not 403).
2. **500 crash on all report routes:** `ReportController` DOES implement `HasMiddleware` but uses the
   removed array format. The vendor pipeline (`staticallyProvidedControllerMiddleware`) wraps arrays in
   `new Middleware($array)` — the whole array becomes the middleware "name", then `->flatten()` spreads
   it into garbage strings (`['auth:sanctum', 'permission:relatorios.view', 'index',
   'permission:relatorios.export', 'download']`), and Laravel tries to instantiate `index`/`download`
   as middleware classes → `BindingResolutionException: Target class [index] does not exist` → 500.

The correct, verified-working pattern already exists in this codebase: `UserController` and
`ActivityLogController` implement `HasMiddleware` and return `new Middleware('permission:slug', only: [...])`
objects — the dump confirms their middleware resolves exactly as intended.

Additional confirmed bugs in BUG-02 scope: `RoleController` has no permission gate at all (any
authenticated user can manage roles/sync permissions — privilege escalation); `RateLimitTest` fails on
`RateLimiter::clear()` (Laravel 13 signature change); `VerificationUatFixTest` imports
`Spatie\Permission\Models\Role` but Spatie is NOT installed (custom RBAC models are used) plus has a
route mismatch; `MaintenanceVerificationTest` hits a non-existent route; `ReportServiceTest` is missing
`InventoryMovementFactory`. For BUG-01: seeding works on a first run but is NOT idempotent — a second
`db:seed` crashes on `EquipmentSeeder` (`Category::create` duplicate), so `migrate --seed` cannot be
re-run.

**Primary recommendation:** Standardize every controller on `implements HasMiddleware` +
`new Middleware('permission:slug', only: [...])` object syntax (UserController is the reference),
make all seeders idempotent, fix the broken test imports/routes, and add a regression test asserting
zero-permission users receive 403 on every module endpoint.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| BUG-01 | Usuário admin consegue logar em ambiente limpo (seeders criam admin/roles/permissões após setup) | Seeders verified working on first `db:seed --force` run (admin@labcontrol.com / @dmin123 created, 6 roles, 31+ permissions). NOT idempotent — 2nd run crashes on EquipmentSeeder (`Category::create`). Clean env observed with 0 users / 14 migrations → `migrate --seed` must be re-runnable. |
| BUG-02 | Bugs encontrados na varredura de verificação são corrigidos e validados | 33 test failures root-caused: middleware misconfiguration (14 controllers silent RBAC bypass + ReportController 500 crash), RateLimiter::clear signature, Spatie import in VerificationUatFixTest, route mismatches in Verification/Maintenance tests, missing InventoryMovementFactory. Fix recipes and regression-test strategy below. |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| AuthN (quem é o usuário) | API / Backend | — | `auth:sanctum` no route group (routes/api.php) — já funciona |
| AuthZ (o que o usuário pode fazer) | API / Backend | — | `permission:*` via custom CheckPermission + Role/Permission models — QUEBRADO (14 controllers sem HasMiddleware) |
| Enforce per-method permissions | API / Backend | — | `new Middleware('permission:x', only: [...])` + `implements HasMiddleware` — padrão correto a aplicar em todos os controllers |
| Regras de negócio (empréstimos, estoque, calibração) | API / Backend | Database | LoanService/MaintenanceService/InventoryMovementService com transações DB |
| Espelhar permissões na UI | Browser / Client | — | Sidebar/filtros por hasPermission() — apenas reflexo; backend é a autoridade |
| Seed de dados iniciais | Database / Storage | API | Seeders criam admin/roles/permissões — precisa ser idempotente (BUG-01) |

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| laravel/framework | 13.20.0 (`^13.8`) | Framework API | Já instalado; pesquisar conforme a v13 (API de middleware mudou) |
| laravel/sanctum | ^4.0 | Autenticação SPA (cookies HttpOnly) | Já instalado; `auth:sanctum` funciona |
| PHP | ^8.3 | Runtime | Container `labcontrol-php` |
| PostgreSQL | container `labcontrol-postgres` | Banco | DBs: labcontrol, labcontrol_staging, labcontrol_testing |
| Redis | container `labcontrol-redis` | Cache/filas | Já configurado no compose |
| phpunit/phpunit | ^12.5.12 | Testes | `php artisan test` |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| App\Models\Role / Permission (custom) | — | RBAC custom (SEM Spatie) | SEMPRE — o projeto usa modelos próprios |
| App\Http\Middleware\CheckPermission | — | Verifica `permission:slug` | Registrado como alias `permission` em bootstrap/app.php |
| maatwebsite/excel, barryvdh/laravel-dompdf, darkaonline/l5-swagger | ^3.1 / ^3.1 / ^8.6 | Relatórios + docs API | Já instalados; usados por ReportController |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `new Middleware()` object syntax | Legacy array format (`['middleware'=>..., 'only'=>...]` ou `'options'=>['only'=>...]`) | Array format REMOVIDO no Laravel 11+ — causa 500 (ReportController) ou bypass silencioso |
| `new Middleware()` object syntax | PHP attributes `#[Middleware(...)]` | Attributes são o padrão mais novo; codebase já usa método estático middleware() nos 3 controllers que funcionam — consistência manda |
| Custom RBAC (Role/Permission models) | spatie/laravel-permission | Spatie NÃO está instalado; migração seria reescrita massiva sem benefício para v1.1 |
| Seeder idempotente (firstOrCreate) | Re-seed manual com truncate | `migrate --seed` precisa ser re-executável — idempotência é o caminho |

**Installation:**
```bash
# Nenhum pacote novo é necessário nesta fase. Não instalar nada.
# (spatie/permission NÃO deve ser adicionado — o projeto usa RBAC custom.)
```

**Version verification:**
```bash
docker compose -f docker/docker-compose.yml exec -T php php artisan --version
# => Laravel Framework 13.20.0  [VERIFIED: composer.lock v13.20.0, npm registry for frontend]
```

## Package Legitimacy Audit

> Esta fase **não instala nenhum pacote novo** — todas as correções usam código já presente
> (Laravel core, controllers, seeders, testes existentes). Nenhum risco de slopsquatting.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| (nenhum pacote novo) | — | — | — | — | — | N/A — sem instalações nesta fase |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

**Nota:** `Spatie\Permission\Models\Role` importado em `backend/tests/Feature/VerificationUatFixTest.php`
NÃO existe no projeto (Spatie não está no composer.json nem no vendor) — é import quebrado, corrigir
para usar os modelos custom, NÃO instalar Spatie.

## Architecture Patterns

### System Architecture (AuthZ flow — current vs. target)

```
Browser (Vue + Pinia)
   │  HttpOnly cookie (Sanctum)
   ▼
routes/api.php  (Route::middleware('auth:sanctum')->group(...))
   │  controller resolved
   ▼
Controller::middleware()  ◄── PROBLEM AREA
   │
   ├─ IMPLEMENTA HasMiddleware? ── NÃO ──► middleware = []  → permissões silenciosamente ignoradas (14 controllers)
   │                                         (auth:sanctum do grupo ainda vale)
   │
   └─ SIM ──► staticallyProvidedControllerMiddleware() [vendor]
                  │
                  ├─ retorno = string ou new Middleware('permission:x', only:[...])
                  │      → resolve normalmente  ✓ (UserController, ActivityLogController)
                  │
                  └─ retorno = array legacy ['middleware'=>..., 'options'=>['only'=>...]]
                         → new Middleware($array)  (array vira o NOME)
                         → ->flatten() gera nomes-lixo: ['auth:sanctum', 'permission:relatorios.view', 'index', ...]
                         → BindingResolutionException: Target class [index] does not exist → 500
                              (ReportController)
   ▼
App\Http\Middleware\CheckPermission (alias 'permission')
   │  admin role? → skip
   │  senão $user->hasPermission($permission)  → 403 se negado
   ▼
Controller method (validação via Form Request → 422)
```

### Recommended Project Structure (alvo após a correção)

```
backend/app/Http/Controllers/Api/V1/
├── UserController.php            # REFERÊNCIA CORRETA: implements HasMiddleware + new Middleware(..., only:[...])
├── ActivityLogController.php     # REFERÊNCIA CORRETA (idem)
├── EquipmentController.php       # corrigir: + implements HasMiddleware, new Middleware() por método
├── ... (12 controllers restantes) # idem
├── ReportController.php          # corrigir: substituir array legacy por new Middleware()
└── RoleController.php            # corrigir: adicionar gate de permissão (ex.: role:gerenciar ou permission:roles.*)

backend/database/seeders/
├── RolePermissionSeeder.php      # já idempotente (updateOrInsert) — manter
├── AdminUserSeeder.php           # já idempotente (checks existência) — manter
├── EquipmentSeeder.php           # CORRIGIR: Category/Manufacturer/Supplier::create → firstOrCreate
├── ... demais seeders            # revisar: Calibration/Verification/Maintenance/Loan (factories) para idempotência

backend/tests/Feature/
├── RbacRegressionTest.php        # NOVO: usuário zero-permissão recebe 403 em TODOS os endpoints de módulo
└── SeederIdempotencyTest.php     # NOVO: rodar seeders 2x e assertar que não quebra / não duplica
```

### Pattern 1: HasMiddleware + Middleware object (correto)
**What:** Classe do controller implementa `HasMiddleware` e o método estático `middleware()` retorna
`new Middleware('permission:slug', only: ['metodo1', ...])`.
**When to use:** SEMPRE que um controller precisa de checks de permissão por método no Laravel 11+.
**Example:**
```php
// backend/app/Http/Controllers/Api/V1/UserController.php — REFERÊNCIA FUNCIONANDO (dump verificado)
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:usuarios.view', only: ['index', 'show']),
            new Middleware('permission:usuarios.create', only: ['store']),
            new Middleware('permission:usuarios.edit', only: ['update']),
            new Middleware('permission:usuarios.delete', only: ['destroy']),
            new Middleware('permission:usuarios.manage', only: ['assignRole', 'syncPermissions', 'toggleStatus']),
        ];
    }
}
```

### Pattern 2: Correção mínima para os 14 controllers afetados
**What:** Adicionar `implements HasMiddleware` e converter cada entrada do array em `new Middleware(...)`.
**Example (EquipmentController):**
```php
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EquipmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:equipamentos.view', only: ['index', 'show', 'byCategory', 'byLocation', 'byDepartment', 'export']),
            new Middleware('permission:equipamentos.create', only: ['store']),
            new Middleware('permission:equipamentos.edit', only: ['update']),
            new Middleware('permission:equipamentos.delete', only: ['destroy']),
            new Middleware('permission:equipamentos.manage', only: ['syncPhotos', 'removePhoto', 'auditLog']),
        ];
    }
}
```
> Nota: o método estático deve ser `public static function middleware(): array` — a assinatura já existe
> nos 14 controllers; só falta o `implements HasMiddleware` + converter arrays em objetos.

### Pattern 3: Correção do ReportController (500)
**What:** Substituir o array legacy por objetos Middleware; checar se `maatwebsite/excel` + `dompdf`
continuam a API esperada na v13 (mantidos na mesma versão instalada).
**Example:**
```php
class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:relatorios.view', only: ['index', 'equipmentReport', 'calibrationReport', 'loanReport', 'inventoryReport', 'stockReport']),
            new Middleware('permission:relatorios.export', only: ['download']),
        ];
    }
}
```

### Anti-Patterns to Avoid
- **`'options' => ['only' => [...]]` dentro de array de middleware:** formato do Laravel 10, causa 500
  (BindingResolutionException) — usar `new Middleware('x', only: [...])`.
- **`implements HasMiddleware` faltando:** o middleware declarado é ignorado silenciosamente — RBAC bypass.
- **Depender só do `auth:sanctum` no route group:** autentica mas NÃO autoriza — cada controller precisa
  do `permission:*`.
- **Criar `php artisan make:controller` novo e reescrever:** reutilizar os controllers existentes; só
  converter o padrão de middleware.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Middleware por método | Arrays legacy `['middleware'=>...]` | `new Middleware('slug', only: [...])` (Laravel core) | Formato oficial; o legacy foi removido e causa 500 |
| Re-implementar RBAC | Modelos Spatie ou novos Role/Permission | RBAC custom existente (App\Models\Role/Permission + CheckPermission) | Já funciona; só falta o wiring do middleware |
| Rate limiter custom | Nova implementação | RateLimiter::for('api') com chave correta (Laravel 13) | `RateLimiter::clear()` mudou de assinatura — ajustar chamadas |
| Seeding | Seeders que assumem banco vazio | `firstOrCreate`/`updateOrInsert` em TODOS os seeders | `migrate --seed` roda em entrypoint/restart |

**Key insight:** Este é um bug de *wiring*, não de arquitetura. A infraestrutura (RBAC custom, Sanctum,
Form Requests) está correta; o único defeito sistemático é a interface entre controllers e o pipeline de
middleware do Laravel 13. A correção é mecânica e de baixo risco, mas exige uma regressão de teste para
garantir que nenhum endpoint fique sem gate.

## Runtime State Inventory

> Nenhum rename/refactor em runtime. Apesar disso, o estado do banco é relevante para BUG-01:

| Category | Items Found | Action Required |
|----------|-------------|------------------|
| Stored data | PostgreSQL `labcontrol` criado via `migrate:fresh` em desenvolvimento (14 migrations, 0 usuários após execução dos testes com RefreshDatabase) | BUG-01: rodar `migrate --seed --force` em ambiente limpo; garantir idempotência |
| Live service config | Containers Docker (nginx, php, postgres, redis) saudáveis via `docker/docker-compose.yml` | Nenhuma mudança |
| OS-registered state | None | — |
| Secrets/env vars | `.env` (backend) com DB_CONNECTION=pgsql, DB_DATABASE=labcontrol, DB_HOST=postgres; `DB_DATABASE=:memory:` no phpunit.xml (testes usam sqlite em memória — não tocam o postgres) | Nenhuma mudança; atenção: alterações de schema exigem `migrate:fresh` local |
| Build artifacts | Nenhum | — |

**Nada encontrado em categoria:** OS-registered state, build artifacts — verificado por inspeção direta.

## Common Pitfalls

### Pitfall 1: RBAC bypass silencioso (falta `implements HasMiddleware`)
**What goes wrong:** `permission:` checks nunca executam; usuários sem permissão acessam tudo.
**Why it happens:** Laravel 13 só lê `middleware()` de classes que implementam `HasMiddleware`.
**How to avoid:** Converter TODOS os 14 controllers de uma vez; verificação automatizada (dump de rotas)
ou teste de regressão.
**Warning signs:** `php artisan route:list` não mostra os middleware por controller; usuário zero-permissão
recebe 200.

### Pitfall 2: 500 `BindingResolutionException: Target class [index] does not exist`
**What goes wrong:** Todas as rotas do ReportController quebram com 500.
**Why it happens:** Array legacy vira o "nome" do Middleware e o `->flatten()` espalha strings-lixo
como se fossem classes de middleware.
**How to avoid:** Sempre `new Middleware('slug', only: [...])`; nunca arrays com chaves `middleware`/`options`.
**Warning signs:** Testes ReportControllerTest/ReportExportTest falham com BindingResolutionException.

### Pitfall 3: Confundir versão do Laravel (docs falam "Laravel 12", instalado é 13)
**What goes wrong:** Copiar exemplos de middleware da documentação antiga (v10/11) produz código que
quebra ou é ignorado.
**Why it happens:** A API de controller middleware foi redesenhada (HasMiddleware + Middleware object).
**How to avoid:** Verificar `php artisan --version` antes de aplicar padrões; usar UserController como
referência local.
**Warning signs:** Código de middleware idêntico ao da doc v10 não produz efeito.

### Pitfall 4: Testes que passavam em Laravel 12 quebram na 13
**What goes wrong:** `RateLimiter::clear()` agora exige chave; `RefreshDatabase` com sqlite :memory:
(phpunit.xml) pode mascarar problemas de banco real.
**Why it happens:** Assinaturas de API internas mudaram entre majors.
**How to avoid:** Rodar a suíte completa (`php artisan test`) antes de dar a fase como concluída.
**Warning signs:** ArgumentCountError em RateLimiter::clear.

### Pitfall 5: Importar `Spatie\Permission\Models\Role` sem o pacote instalado
**What goes wrong:** VerificationUatFixTest falha com "Class not found"; confusão sobre qual RBAC usar.
**Why it happens:** Código copiado de outro projeto/LLM sem validar dependências.
**How to avoid:** Usar `App\Models\Role` e `App\Models\Permission`; rodar `composer show` antes de confiar
em imports.
**Warning signs:** "Class Spatie\Permission\Models\Role not found".

## Code Examples

Padrões verificados por dump do pipeline real + leitura do vendor:

### Correção total de um controller afetado (padrão aplicável aos 14)
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
// ... outros imports

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:categorias.view', only: ['index', 'show']),
            new Middleware('permission:categorias.create', only: ['store']),
            new Middleware('permission:categorias.edit', only: ['update']),
            new Middleware('permission:categorias.delete', only: ['destroy']),
        ];
    }
    // ... métodos
}
```
> **Importante:** o método `middleware()` JÁ EXISTE nos 14 controllers (retornando arrays legacy). A
> correção é: (1) adicionar `implements HasMiddleware`, (2) converter cada item do array para
> `new Middleware('permission:x', only: [...])`, (3) conferir os slugs reais usados em cada módulo
> (ex.: `equipamentos.*`, `categorias.*`, `fornecedores.*`, `fabricantes.*`, `inventario.*`,
> `movimentacoes.*`, `emprestimos.*`, `calibracoes.*`, `verificacoes.*`, `manutencao.*`, `dashboard.view`)
> contra o `RolePermissionSeeder` — os slugs NÃO devem ser inventados.

### Lista exata dos controllers afetados (verificada por dump de rotas + leitura de código)
1. **Sem `implements HasMiddleware` (14 — bypass silencioso):** CalibrationCertificateController,
   CalibrationController, CategoryController, DashboardController, EquipmentController,
   EquipmentPhotoController, InventoryCategoryController, InventoryItemController,
   InventoryMovementController, LoanController, MaintenanceOrderController, ManufacturerController,
   SupplierController, VerificationController.
2. **Com HasMiddleware mas formato array legacy (1 — 500):** ReportController.
3. **Sem gate de permissão nenhum (1 — escalonamento):** RoleController (só `auth:sanctum`; métodos
   `store/update/destroy/syncPermissions` sem `permission:roles.*`).
4. **Referência correta (já funcionando):** UserController, ActivityLogController.

### Correção do RateLimiter::clear (assinatura Laravel 13)
```php
// ANTES (quebra): RateLimiter::clear();
// DEPOIS (Laravel 13): exige chave
RateLimiter::clear('api');
```
> Verificar chamadas em produção além do teste: `grep -r "RateLimiter::clear" app/ tests/`.

### Correção de rotas nos testes (404)
```php
// MaintenanceVerificationTest busca /api/v1/maintenance-orders/by-equipment/{id}
// A rota real registrada é: GET /api/v1/equipments/{equipment}/maintenance
$this->getJson("/api/v1/equipments/{$equipment->id}/maintenance")
     ->assertStatus(200);

// VerificationUatFixTest busca /api/v1/verifications/by-equipment/{id}
// A rota real registrada é: GET /api/v1/equipments/{equipment}/verifications
$this->getJson("/api/v1/equipments/{$equipment->id}/verifications")
     ->assertStatus(200);
```
> Referência: `backend/routes/api.php` — rotas `equipments.verifications` (VerificationController@byEquipment)
> e `equipments.maintenance` (MaintenanceOrderController@byEquipment).

### Fix do EquipmentSeeder (idempotência — BUG-01)
```php
// ANTES: Category::create($cat) → "duplicate key value violates unique constraint" na 2ª execução
// DEPOIS:
$category = Category::firstOrCreate(['name' => $cat['name']], $cat);
// Mesma regra para Manufacturer::create e Supplier::create no EquipmentSeeder
// (Equipment já usa Equipment::firstOrCreate na linha ~46 — estender o padrão ao restante)
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Array middleware em controllers (`['middleware'=>..., 'only'=>...]`) | `implements HasMiddleware` + `new Middleware('slug', only: [...])` | Laravel 11 (2024); enforcement na v13 | Arrays são ignorados/transformados em 500 — causa dos bugs desta fase |
| `RateLimiter::clear()` sem argumento | Exige chave (`RateLimiter::clear('api')`) | Laravel 12/13 | Testes e código de rate-limit precisam da chave |
| Spatie/permission como padrão de RBAC | RBAC custom (models Role/Permission + CheckPermission) | Decisão do projeto (não Spatie) | Manter custom; corrigir imports de teste |
| PrimeVue 4 | PrimeVue 5 (exige chave PrimeUI; aviso "Invalid PrimeUI License") | v5 (recente, ~2025) | Remover/silenciar aviso no dev (Pilar 2 PLANNER) |

**Deprecated/outdated:**
- **Array-format controller middleware:** removido; substituído por HasMiddleware/Middleware object.
- **`Spatie\Permission\Models\Role` import em VerificationUatFixTest:** import quebrado; usar App\Models.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Os slugs de permissão usados nos 14 controllers correspondem aos slugs criados no RolePermissionSeeder (não inventei novos) | Code Examples | Se um slug não existir, `hasPermission` retorna false → bloqueio incorreto (401/403 em usuários legítimos). Mitigação: conferir cada slug contra o RolePermissionSeeder/DB antes de aplicar |
| A2 | O `permission:relatorios.export` (ReportController) é o slug correto para o método `download` | Patterns | Se divergir do RolePermissionSeeder, usuário admin pode ser barrado em exportação |
| A3 | RateLimiter::clear('api') é a chave correta no app (o teste usa 'api') | Code Examples | Se o app usa chave diferente, o teste continuaria passando mas o prod continuaria quebrado — verificar com grep |
| A4 | Não há outros controllers com `getMiddleware`/`middleware()` fora dos listados | Common Pitfalls | Novo controller criado depois poderia repetir o bug — a regressão de teste cobre isso |
| A5 | O aviso "Invalid PrimeUI License" é o único impacto da falta de chave PrimeUI (sem impacto runtime em prod) | State of the Art | Se houver impacto real em prod, o Pilar 2 do PLANNER exigiria a chave comunitária gratuita — confirmar com o usuário |

## Open Questions

1. **PrimeUI license (Pilar 2 PLANNER):** registrar chave comunitária gratuita vs. silenciar via CSS?
   - O que sabemos: PrimeVue 5 exige chave; `main.ts` não tem `license` config; aviso aparece no dev.
   - O que falta: preferência do usuário.
   - Recomendação: perguntar no discuss-phase; na ausência de resposta, silenciar o aviso (mudança mínima).

2. **RoleController sem gate de permissão:** adicionar `permission:roles.*` é intencional (só admin gerencia)?
   - O que sabemos: hoje qualquer usuário autenticado pode `store/update/destroy/syncPermissions` de roles.
   - O que falta: confirmar que apenas admin (role `admin` com bypass) deve gerir roles/permissões.
   - Recomendação: aplicar `new Middleware('permission:roles.manage', only: [...])` — alinhado ao padrão
     `usuarios.manage`.

3. **Laravel 12 vs 13 na documentação:** atualizar a doc (PLANNER/docs) para refletir Laravel 13.20?
   - O que sabemos: composer.lock tem v13.20.0; CONTEXT/PLANNER citam "Laravel 12".
   - Recomendação: registrar no changelog/README a versão real; sem mudança de código.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Docker Compose | Backend + frontend + DB | ✓ | containers nginx/php/postgres/redis up | — |
| PHP (container) | Laravel 13.20.0 | ✓ | ^8.3 | — |
| Composer (container) | instalação de dependências | ✓ | — | — |
| PostgreSQL (container) | DB labcontrol | ✓ | — | sqlite :memory: nos testes (phpunit.xml) |
| Redis (container) | cache/filas | ✓ | — | — |
| Node/npm (host) | frontend PrimeVue | ✓ (assumido; pnpm-lock presente) | — | — |
| Artisan CLI | migrações/seeders/testes | ✓ | 13.20.0 | — |

**Dependências ausentes sem fallback:** nenhuma — ambiente Docker completo e saudável.

## Validation Architecture

> `workflow.nyquist_validation` não está explicitamente false → tratado como habilitado.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5.12 (Laravel 13.20.0) |
| Config file | `backend/phpunit.xml` (DB sqlite :memory:) |
| Quick run command | `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=ReportControllerTest` |
| Full suite command | `docker compose -f docker/docker-compose.yml exec -T php php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BUG-01 | Seeders rodam 2x sem erro (idempotência) | unit/feature | `php artisan db:seed --force` (2x, sem exception) | ❌ Wave 0 — novo SeederIdempotencyTest |
| BUG-02 | Middleware do controller resolve permissões corretas | feature/regression | `php artisan test --filter=RbacRegressionTest` (403 para zero-permissão em todos os endpoints de módulo) | ❌ Wave 0 — novo |
| BUG-02 | ReportController não retorna 500 | feature | `php artisan test --filter=ReportControllerTest` | ✅ existe (falha hoje) |
| BUG-02 | RateLimiter::clear com chave | unit | `php artisan test --filter=RateLimitTest` | ✅ existe (falha hoje) |
| BUG-02 | Verification/Maintenance usam rotas corretas | feature | `php artisan test --filter=VerificationUatFixTest` e `--filter=MaintenanceVerificationTest` | ✅ existem (falham hoje) |
| BUG-02 | ReportServiceTest acha InventoryMovementFactory | unit | `php artisan test --filter=ReportServiceTest` | ✅ existe (falha hoje) |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=<teste-da-tarefa>`
- **Per wave merge:** `php artisan test` (suíte completa)
- **Phase gate:** suíte completa verde (124+ testes) antes de `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `backend/tests/Feature/RbacRegressionTest.php` — 403 p/ zero-permissão em todos os endpoints de módulo
- [ ] `backend/tests/Feature/SeederIdempotencyTest.php` — db:seed 2x sem exception
- [ ] Ajustar `ReportControllerTest`/`ReportExportTest` para o novo middleware (após fix)
- [ ] Ajustar `RateLimitTest` para `RateLimiter::clear('api')`
- [ ] Ajustar `VerificationUatFixTest` (import App\Models\Role; chaves esperadas) e `MaintenanceVerificationTest` (rota correta)
- [ ] Criar `Database\Factories\InventoryMovementFactory` (falta)

## Security Domain

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum (cookies HttpOnly) + `auth:sanctum` no route group |
| V3 Session Management | yes | Sanctum SPA cookie; logout revoga token |
| V4 Access Control | **yes (crítico)** | CheckPermission + RBAC custom; **fix: HasMiddleware + new Middleware em todos os controllers** |
| V5 Input Validation | yes | Form Requests (422 já validado nos probes) |
| V6 Cryptography | yes | bcrypt p/ senhas; Sanctum tokens |

### Known Threat Patterns
| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| RBAC bypass (zero-permissão acessa endpoints) | Elevation of Privilege | `implements HasMiddleware` + `new Middleware('permission:x')` em todos os controllers; teste de regressão 403 |
| 500 em rotas de relatório | DoS (parcial) | Converter ReportController p/ formato objeto; teste ReportControllerTest |
| Escalonamento via RoleController (qualquer autenticado gerencia roles) | Elevation of Privilege | Gate `permission:roles.manage` (ou equivalentes) nos métodos store/update/destroy/syncPermissions |
| CSRF em mutações | Tampering | Sanctum SPA (cookie) exige cabeçalho `X-XSRF-TOKEN`; manter |
| Injection (SQL) | Tampering | Eloquent/Query Builder (sem SQL cru) — manter |

## Sources

### Primary (HIGH confidence)
- **Dump do pipeline real:** `php artisan tinker` (controllerMiddleware de cada controller) + probes HTTP
  com usuário zero-permissão (200/422 vs 403) — empirical, run nesta sessão.
- **Vendor source:** `backend/vendor/laravel/framework/src/Illuminate/Routing/Route.php`
  (`staticallyProvidedControllerMiddleware`, linhas ~1154-1172) e
  `Illuminate/Routing/Controllers/Middleware.php` (constructor: `only`/`except` ignorados quando o array
  vira o nome; `->flatten()` espalha strings) — confirmado por leitura direta.
- **Laravel docs (Context7):** "Controller Middleware" — `HasMiddleware` + `new Middleware('name', only: [...])`
  como o padrão; arrays legacy removidos.
- **Código do app:** `backend/app/Http/Controllers/Api/V1/*` (15 controllers + UserController/ActivityLogController
  como referência), `bootstrap/app.php` (alias `permission`), `app/Http/Middleware/CheckPermission.php`,
  `app/Models/{Role,Permission,User}.php`, `routes/api.php`.
- **Suíte de testes real:** `php artisan test` (33 failed / 124 passed) — logs completos analisados.

### Secondary (MEDIUM confidence)
- **PrimeVue 5 / PrimeUI license:** PrimeVue v5 requer chave PrimeUI; comunidade gratuita disponível
  (`npm view primevue version` → 5.x); aviso dev-time "Invalid PrimeUI License".
- **composer.lock / npm registry:** laravel/framework 13.20.0; sanctum ^4.0; phpunit ^12.5.12.

### Tertiary (LOW confidence)
- **PrimeUI license impacto runtime em prod:** não testado em build de produção — a confirmar
  (assumption A5).

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — verificado contra composer.lock, vendor, containers ativos.
- Architecture: HIGH — padrão de correção validado por dump do pipeline real + 2 controllers de referência
  funcionando + leitura do vendor (mecanismo exato do bug).
- Pitfalls: HIGH — todos reproduzidos empiricamente nesta sessão (200/422/500/404/Class not found).

**Research date:** 2026-08-09
**Valid until:** 2026-09-08 (30 dias; Laravel 13 em movimento rápido — revalidar se a fase atrasar)
