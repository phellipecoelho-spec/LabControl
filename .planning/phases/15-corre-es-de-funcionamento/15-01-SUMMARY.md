---
phase: 15-corre-es-de-funcionamento
plan: 15-01
subsystem: api
tags: [laravel, rbac, middleware, hasmiddleware, permission, dompdf, maatwebsite-excel, rate-limit, testing]

# Dependency graph
requires:
  - phase: 14-03
    provides: Failing tests (RateLimitTest, VerificationUatFixTest, MaintenanceVerificationTest) and domain services
provides:
  - RBAC enforcement on 14 Api/V1 controllers (HasMiddleware + new Middleware pattern)
  - RoleController privilege escalation closed (roles.manage on mutations)
  - ReportController legacy middleware format converted to Laravel 13 new Middleware()
  - ReportService fixed for dompdf v3 API (setOptions + Response return type)
  - barryvdh/laravel-dompdf and maatwebsite/excel actually installed (composer.lock/vendor synced)
  - RbacRegressionTest network proving 403 for role-less users across all modules
  - RateLimitTest green (login rate limiting fixed to check-after-failure contract)
affects: [15-02]

# Tech tracking
tech-stack:
  added:
    - barryvdh/laravel-dompdf v3.1.2 (vendor install)
    - maatwebsite/excel 3.1.69 (vendor install)
  patterns:
    - Laravel 13 controller middleware: `implements HasMiddleware` + `new Middleware('permission:x', only: [...])`
    - Login rate limiting: check-after-failed-attempt (only failures count, success clears, custom PT message)
    - Regression tests: role-less user expects 403, admin bypass expected 200

key-files:
  created:
    - backend/tests/Feature/RbacRegressionTest.php
    - backend/database/factories/InventoryMovementFactory.php
  modified:
    - backend/app/Http/Controllers/Api/V1/{Category,Manufacturer,Supplier,Equipment,EquipmentPhoto,InventoryCategory,InventoryItem,InventoryMovement,Loan,Calibration,CalibrationCertificate,Verification,MaintenanceOrder,Dashboard,Report,Role}Controller.php
    - backend/app/Http/Controllers/Api/V1/AuthController.php
    - backend/app/Services/ReportService.php
    - backend/database/seeders/RolePermissionSeeder.php
    - backend/routes/api.php
    - backend/tests/Feature/Auth/RateLimitTest.php
    - backend/composer.json (already declared) / composer.lock (synced)

key-decisions:
  - "Role-less user receives 403 on all protected module endpoints; GET /api/v1/roles index/show stays open (user screen needs role list)"
  - "Login rate limit moved to check-after-failed-attempt: only failed attempts count, successful login clears counter, custom PT-BR message"
  - "throttle:auth removed from POST /auth/login only (register/verify/forgot/reset keep it) because generic middleware counts all requests and never clears on success"
  - "Dompdf v3 API used: setOptions() for option arrays; download() returns Illuminate\\Http\\Response (not StreamedResponse)"

patterns-established:
  - "Controller RBAC: static middleware() returning new Middleware('permission:slug', only: [...]) with HasMiddleware interface"
  - "CheckPermission grants admin bypass; users without the required permission throw AuthorizationException (403)"

requirements-completed: [BUG-02]

coverage:
  - id: D1
    description: "RBAC bypass eliminado - 14 controllers Api/V1 convertidos para HasMiddleware + new Middleware('permission:x')"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Feature/RbacRegressionTest.php (14 tests, 23 assertions)"
        status: pass
    human_judgment: false
  - id: D2
    description: "RoleController escalada de privilegio fechada - mutacoes exigem permission:roles.manage"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Feature/RbacRegressionTest.php#test_role_mutations_require_roles_manage_permission"
        status: pass
    human_judgment: false
  - id: D3
    description: "ReportController sem 500 - middleware legado convertido e dependencias dompdf/excel instaladas (ReportControllerTest verde)"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Feature/ReportControllerTest.php (15 tests, 39 assertions)"
        status: pass
    human_judgment: false
  - id: D4
    description: "RateLimitTest verde - login limita apos 5 falhas (429 com mensagem PT), sucesso limpa o contador"
    requirement: BUG-02
    verification:
      - kind: integration
        ref: "backend/tests/Feature/Auth/RateLimitTest.php (3 tests, 20 assertions)"
        status: pass
    human_judgment: false
  - id: D5
    description: "Rota GET /api/v1/verifications/pending aponta para VerificationController::pending (verificacao - nenhuma mudanca necessaria)"
    requirement: BUG-02
    verification:
      - kind: other
        ref: "backend/routes/api.php:126 (route inspection)"
        status: pass
    human_judgment: false

# Metrics
duration: 360min
completed: 2026-08-09
status: complete
---

# Phase 15 Plan 1: RBAC Enforcement e Correções de Controllers (BUG-02) Summary

**Bypass de RBAC eliminado em 14 controllers Api/V1 (padrão Laravel 13 HasMiddleware + new Middleware), escalada de privilégio do RoleController fechada com roles.manage, ReportController sem 500 (dompdf v3 + maatwebsite/excel instalados), e rede de regressão RbacRegressionTest comprovando 403 para usuário sem permissão em todos os módulos**

## Performance

- **Duration:** 360 min (6h — inclui espera do checkpoint human-verify de pacote e instalação das dependências de relatório)
- **Started:** 2026-08-09T17:17:16Z
- **Completed:** 2026-08-09T23:16:50Z
- **Tasks:** 3
- **Files modified:** 23 (16 controllers, 1 service, 1 seeder, 1 route file, 2 testes, 1 factory criada, composer.lock)

## Accomplishments

- **RBAC bypass crítico eliminado:** 14 controllers (Equipamentos/Estoque + Operações) convertidos de `middleware()` sem interface para `implements HasMiddleware` + `new Middleware('permission:x', only: [...])` — o router agora aplica as permissões; usuário sem role recebe 403.
- **Escalada de privilégio no RoleController fechada:** qualquer usuário autenticado podia criar/editar/excluir roles e sincronizar permissões; agora exige `roles.manage` (permissão adicionada ao RolePermissionSeeder).
- **ReportController 500 corrigido:** formato legado de middleware (`['middleware' => ..., 'options' => ...]`) removido no Laravel 13 convertido para `new Middleware()`; causa raiz real dos 500 era a API dompdf v3 (`setOption` array → `setOptions`, `download()` retorna `Illuminate\Http\Response`, não `StreamedResponse`).
- **Dependências de relatório instaladas:** barryvdh/laravel-dompdf v3.1.2 e maatwebsite/excel 3.1.69 baixadas e sincronizadas (composer.lock + vendor + autoload regenerado).
- **RbacRegressionTest criado (14 testes, 23 assertions):** rede de regressão de fim-de-estado provando 403 para usuário sem roles em dashboard, equipamentos, categorias/fabricantes/fornecedores, estoque, movimentações, empréstimos, calibrações, aferições (+ pending), manutenções, relatórios, usuários, logs e mutações de roles; garante que `GET /api/v1/roles` (leitura) permanece aberto e que admin bypassa.
- **RateLimitTest verde:** contrato corrigido — apenas tentativas *falhas* contam, login bem-sucedido limpa o contador, bloqueio no 6º hit (429 com mensagem PT-BR).
- **Suite completa:** 157 passed, 6 failed — as 6 falhas restantes são exclusivamente VerificationUatFixTest (5) e MaintenanceVerificationTest (1), documentadas no plano como pendentes do 15-02.

## Task Commits

Cada tarefa foi commitada atomicamente:

1. **Task 1: Converter 7 controllers de Equipamentos/Estoque para HasMiddleware** - `b645be7` (fix)
2. **Task 2: Converter 7 controllers de Operações + ReportController + RoleController + InventoryMovementFactory** - `7a45b8f` (refactor)
3. **Task 3: RbacRegressionTest + correção RateLimitTest/AuthController** - `16af09a` (test)

**Plan metadata:** pendente — commit docs após este SUMMARY.

## Files Created/Modified

- `backend/app/Http/Controllers/Api/V1/CategoryController.php` - HasMiddleware + equipamentos.view/create/edit/delete
- `backend/app/Http/Controllers/Api/V1/ManufacturerController.php` - idem
- `backend/app/Http/Controllers/Api/V1/SupplierController.php` - idem
- `backend/app/Http/Controllers/Api/V1/EquipmentController.php` - idem (guards de exclusão preservados)
- `backend/app/Http/Controllers/Api/V1/EquipmentPhotoController.php` - HasMiddleware
- `backend/app/Http/Controllers/Api/V1/InventoryCategoryController.php` - estoque.view/create/edit/delete
- `backend/app/Http/Controllers/Api/V1/InventoryItemController.php` - idem
- `backend/app/Http/Controllers/Api/V1/InventoryMovementController.php` - movimentacoes.view/create
- `backend/app/Http/Controllers/Api/V1/LoanController.php` - emprestimos.view/create/edit/finalizar
- `backend/app/Http/Controllers/Api/V1/CalibrationController.php` - calibracoes.view/create/edit/concluir/cancel
- `backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php` - certificados.view/upload
- `backend/app/Http/Controllers/Api/V1/VerificationController.php` - afericoes.view/create/edit (inclui pending)
- `backend/app/Http/Controllers/Api/V1/MaintenanceOrderController.php` - manutencoes.view/create/edit/concluir
- `backend/app/Http/Controllers/Api/V1/DashboardController.php` - dashboard.view
- `backend/app/Http/Controllers/Api/V1/ReportController.php` - middleware legado → new Middleware() (auth:sanctum, relatorios.view/export)
- `backend/app/Http/Controllers/Api/V1/RoleController.php` - roles.manage em store/update/destroy/syncPermissions
- `backend/app/Http/Controllers/Api/V1/AuthController.php` - rate limit movido para check-após-falha
- `backend/app/Services/ReportService.php` - API dompdf v3 (setOptions, Response)
- `backend/database/seeders/RolePermissionSeeder.php` - permissão roles.manage
- `backend/routes/api.php` - throttle:auth removido apenas do POST /auth/login
- `backend/tests/Feature/RbacRegressionTest.php` - criado (rede de regressão RBAC)
- `backend/tests/Feature/Auth/RateLimitTest.php` - limpa chave real login:127.0.0.1
- `backend/database/factories/InventoryMovementFactory.php` - criado (ReportControllerTest)
- `backend/composer.lock` - sync com composer.json (dompdf, excel, l5-swagger)

## Decisions Made

- **`GET /api/v1/roles` permanece aberto para leitura** (index/show): a tela de usuários carrega a lista de roles; apenas mutações exigem `roles.manage`. Comprovado no RbacRegressionTest.
- **Rate limiting do login no controller (não no middleware de rota):** o padrão "check-after-failed-attempt" permite que login bem-sucedido limpe o contador e retorne a mensagem PT-BR — comportamento exigido pelos testes existentes (RateLimitTest).
- **`throttle:auth` removido do login apenas:** o middleware genérico conta *todas* as requisições, nunca limpa em sucesso e retorna 429 em inglês antes do controller — incompatível com o contrato de "5 falhas → bloqueio, sucesso → limpa". Mantido em register/verify/forgot/reset (que não têm limiter próprio).
- **ReportService usa API v3 do dompdf:** `setOptions(['dpi' => ..., 'defaultFont' => ...])` (v3 `setOption($attr, $value)` é 2-args) e aceita `Illuminate\Http\Response` no retorno (v3 `download()` não é mais `StreamedResponse`).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Dependências de relatório declaradas mas nunca instaladas (composer.lock/vendor dessincronizados)**
- **Found during:** Task 2 (verificação do ReportControllerTest)
- **Issue:** Os 500 do ReportController (pdf/xlsx) eram `Class "Barryvdh\DomPDF\Facade\Pdf" not found` / `Maatwebsite\Excel\Facades\Excel not found`. O commit `cb40792` "install" só editou composer.json e criou config/dompdf.php — nunca rodou `composer update`; composer.lock tinha 0 referências e vendor não continha os pacotes. O research do plano assumiu "Já instalados".
- **Fix:** Checkpoint human-verify (gate blocking-human, Regra 3 exclusão de package install) → usuário confirmou "verified" → `composer update barryvdh/laravel-dompdf maatwebsite/excel` como root (permissão de escrita no volume); tmp-zip do swagger-ui travado por lock do Windows removido manualmente; `composer dump-autoload` regenerou o autoloader (8866 classes).
- **Files modified:** backend/composer.lock, backend/vendor/*
- **Verification:** ReportControllerTest 15 passed / 39 assertions.
- **Committed in:** 7a45b8f (Task 2)

**2. [Rule 1 - Bug] ReportService usa API v2 do dompdf — incompatível com v3**
- **Found during:** Task 2 (após instalar os pacotes, PDF ainda retornava 500)
- **Issue:** `streamPdf()` chamava `setOption(['dpi' => ..., ...])` (v3 exige `setOptions()` para array) e declarava retorno `StreamedResponse` enquanto v3 `download()` retorna `Illuminate\Http\Response` (classe irmã) → TypeError → 500. Teste direto do call chain confirmou.
- **Fix:** `setOption(array)` → `setOptions(array)`; tipo de retorno de `streamPdf()` e das uniões `StreamedResponse|BinaryFileResponse` ampliado para incluir `Illuminate\Http\Response` (service + ReportController::download).
- **Files modified:** backend/app/Services/ReportService.php, backend/app/Http/Controllers/Api/V1/ReportController.php
- **Verification:** ReportControllerTest 15 passed (pdf, xlsx, csv, calibrações, movimentações, dashboard).
- **Committed in:** 7a45b8f (Task 2)

**3. [Rule 1 - Bug] Rate limiting do login duplicado e com contrato errado (throttle:auth + check-before-auth)**
- **Found during:** Task 3 (RateLimitTest: 2 de 3 testes vermelhos mesmo após fix da chave)
- **Issue:** (a) o middleware `throttle:auth` na rota conta TODAS as requisições, nunca limpa em sucesso e retorna 429 em inglês ANTES do controller — quebrava "successful login clears rate limit" (200 esperado, 429 recebido) e a mensagem PT; (b) o controller checava `tooManyAttempts` ANTES da autenticação, então após 5 falhas até login correto era bloqueado (429) — contrato dos testes exige que só falhas contem e sucesso limpe.
- **Fix:** Movido o check para dentro do bloco de falha (`if (!Auth::attempt(...))` → check → hit → 422; sucesso → `RateLimiter::clear($key)`); removido `throttle:auth` apenas do POST /auth/login; teste agora limpa a chave real `login:127.0.0.1`.
- **Files modified:** backend/app/Http/Controllers/Api/V1/AuthController.php, backend/routes/api.php, backend/tests/Feature/Auth/RateLimitTest.php
- **Verification:** RateLimitTest 3 passed / 20 assertions; suite Auth completa verde.
- **Committed in:** 16af09a (Task 3)

---

**Total deviations:** 3 auto-fixed (1 blocking, 2 bugs)
**Impact on plan:** Todos os auto-fixes foram necessários para atingir o done criteria do plano (ReportControllerTest, RateLimitTest e RbacRegressionTest verdes). Sem escopo extra — nenhum endpoint/feature novo; mudanças restritas aos arquivos do plano + AuthController/routes (necessários para o contrato de rate limit).

## Issues Encountered

- **Permissão de escrita no volume Docker (Windows):** composer.lock era root:644 e o container roda como labcontrol (uid 1000) → `composer update` falhou com "Permission denied". Resolvido rodando composer como root (`-u root`), consistente com o estado existente (vendor já era root-owned).
- **tmp-zip do swagger-ui travado:** "Could not delete vendor/composer/tmp-*.zip" (lock de arquivo do host Windows) abortou a instalação no passo 20/21, deixando o autoloader sem regenerar. Removido o zip pelo host e rodado `composer dump-autoload`.
- **Falhas restantes da suite completa (6):** VerificationUatFixTest (5 — inclui 403s que agora são o comportamento RBAC correto pós-fix, e testes usando rota legada `/verifications/by-equipment/{id}`) e MaintenanceVerificationTest (1 — rota legada `/maintenance-orders/by-equipment/{id}`). Todas documentadas no plano como pendentes do 15-02 (migração de rotas canônicas + ajuste de permissões).

## User Setup Required

None - não há configuração manual de serviços externos. Dependências de relatório (dompdf/excel) instaladas no container; se o container for recriado, `composer install` usa o composer.lock já sincronizado.

## Next Phase Readiness

- **15-02 pronto para:** migrar VerificationUatFixTest e MaintenanceVerificationTest para as rotas canônicas (`/equipments/{equipment}/verifications` e `/equipments/{equipment}/maintenance`), ajustar os 403s (usuários de teste precisam de roles/permissões agora que RBAC está ativo) e o `assignRole()` inexistente (User::assignRole — modelo não usa Spatie), além das falhas conhecidas (ReportServiceTest, SeederIdempotencyTest).
- **Frontend:** páginas Vue de equipamentos/estoque (mudanças da sessão anterior, não commitadas) precisam de integração no 15-02.
- **Atenção:** usuários seedados sem permissões agora recebem 403 em todos os módulos — qualquer teste/fixture que crie usuário sem role deve atribuir roles.

## Self-Check: PASSED

- FOUND: backend/tests/Feature/RbacRegressionTest.php
- FOUND: backend/database/factories/InventoryMovementFactory.php
- FOUND: .planning/phases/15-corre-es-de-funcionamento/15-01-SUMMARY.md
- FOUND: b645be7 (Task 1), 7a45b8f (Task 2), 16af09a (Task 3)

---
*Phase: 15-corre-es-de-funcionamento*
*Completed: 2026-08-09*
