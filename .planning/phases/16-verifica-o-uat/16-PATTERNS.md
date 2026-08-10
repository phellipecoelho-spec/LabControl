# Phase 16: Verificação UAT — Pattern Map

**Mapped:** 2026-08-09
**Files analyzed:** 12 (1 novo artefato primário + 7 commits pendentes + 4 suítes de apoio existentes)
**Analogs found:** 5 / 5

> **Natureza da fase:** verificação-only. **Nenhum código de produto é criado ou alterado.** O único artefato novo é `16-UAT.md` (documento de registro dos 11 cenários UAT). Os 7 arquivos "tocados" já existem e têm mudanças pendentes na working tree — o trabalho é *commitar, executar, observar e documentar*, não escrever código. Os analog patterns vêm dos artefatos de verificação/UAT das fases 09, 10 e 15.

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|-------------------|------|-----------|----------------|---------------|
| `.planning/phases/16-verifica-o-uat/16-UAT.md` (NEW) | verification doc (UAT register) | manual request-response (walkthrough UI) | `.planning/phases/15-corre-es-de-funcionamento/15-UAT.md` | exact |
| `.planning/phases/16-verifica-o-uat/16-UAT.md` (content) | verification doc | manual | `.planning/phases/09-afericoes/09-UAT.md` (via `git show ddff1a5^`) | exact (fonte canônica dos 6 cenários UAT-01) |
| `.planning/phases/16-verifica-o-uat/16-UAT.md` (content) | verification doc | manual | `.planning/phases/10-manutencaoes/10-VERIFICATION.md` (via `git show ddff1a5^`) | exact (fonte canônica dos 5 itens UAT-02) |
| `.planning/phases/16-verifica-o-uat/16-PLAN.md`(s) (NEW, planner) | plan doc | batch (verification tasks) | `.planning/phases/15-corre-es-de-funcionamento/15-01-PLAN.md` | exact |
| `backend/app/Models/Equipment.php` (COMMIT only) | model | CRUD | — (já existe; só commitar) | n/a — no pattern needed |
| `backend/app/Services/LoanService.php` (COMMIT only) | service | CRUD/state-transition | — (já existe; só commitar) | n/a |
| `backend/app/Services/MaintenanceService.php` (COMMIT only) | service | CRUD/state-transition | — (já existe; só commitar) | n/a |
| `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` (COMMIT only) | component | request-response | — (já existe; só commitar) | n/a |
| `frontend/src/modules/equipment/pages/EquipmentFormPage.vue` (COMMIT only) | component | request-response | — (já existe; só commitar) | n/a |
| `frontend/src/modules/equipment/pages/EquipmentListPage.vue` (COMMIT only) | component | request-response | — (já existe; só commitar) | n/a |
| `frontend/src/modules/inventory/pages/InventoryItemFormPage.vue` (COMMIT only) | component | request-response | — (já existe; só commitar) | n/a |
| `backend/tests/Feature/VerificationUatFixTest.php` (EVIDENCE only) | test | batch (automated support) | — (existe e está verde 5/28) | n/a — executar como evidência |
| `backend/tests/Feature/MaintenanceVerificationTest.php` (EVIDENCE only) | test | batch | — (existe e está verde 6/23) | n/a |
| `backend/tests/Feature/RbacRegressionTest.php` (EVIDENCE only) | test | batch | — (existe e está verde 14/23) | n/a |
| `backend/tests/Feature/SeederIdempotencyTest.php` (EVIDENCE only) | test | batch | — (existe e está verde 2/14) | n/a |

## Pattern Assignments

### `16-UAT.md` (verification doc — formato XX-UAT.md)

**Analog:** `15-UAT.md` (formato) + `09-UAT.md` (fonte dos 6 cenários) + `10-VERIFICATION.md` (fonte dos 5 itens)

**Frontmatter YAML** (copiar de `15-UAT.md` linhas 1-7, mesma estrutura em `09-UAT.md`):
```markdown
---
status: testing
phase: 16-verifica-o-uat
source: 16-01-SUMMARY.md, 16-02-SUMMARY.md
started: <data ISO>
updated: <data ISO>
---
```

**Seção `Current Test`** (copiar de `15-UAT.md` linhas 9-15 e `09-UAT.md` — cenário atual com `awaiting: user response`):
```markdown
## Current Test

number: 1
name: DataTable de Aferições Pendentes — layout, loading e estado vazio
expected: |
  Página `/verifications` carrega DataTable com colunas: Equipamento, Patrimônio, Nº Série, Categoria, Frequência, Última Aferição, Ações.
  Loading skeleton visível durante carregamento.
  Se não houver pendentes, exibe estado vazio "Todos os equipamentos estão em dia".
awaiting: user response
```

**Lista `Tests`** — cada cenário com `expected` + `result` + `source` + `coverage_id` (padrão de `15-UAT.md` linhas 17-85; **`coverage_id` é o link de rastreabilidade para o requisito**):
```markdown
### 4. Alerta de tolerância excedida após salvar aferição
expected: Ao salvar uma aferição com algum parâmetro fora da tolerância, um Toast de aviso (warn) é exibido com a mensagem "Tolerância excedida para o equipamento".
result: pass          # ou: fail (com motivo) | pending
source: manual        # ou: automated (com ref de teste)
coverage_id: UAT-01-S4
```

**Summary + Gaps** (copiar de `15-UAT.md` linhas 87-98):
```markdown
## Summary

total: 11
passed: 0
issues: 0
pending: 11
skipped: 0
blocked: 0

## Gaps

[none yet]
```

**Conteúdo dos 6 cenários UAT-01** — recuperar textualmente do git (NÃO recriar de memória — 16-RESEARCH.md "Don't Hand-Roll" #5):
```bash
git show ddff1a5^:.planning/phases/09-afericoes/09-UAT.md
```
Cenários (09-UAT.md): 1) DataTable pendentes — layout/loading/empty; 2) Formulário com campos dinâmicos de parâmetros; 3) Aba Aferições (tab 3) — timeline com parâmetros expansíveis; 4) Toast warn "Tolerância excedida" ao salvar fora do intervalo; 5) Aba condicional a `afericoes.view`; 6) Botão "Aferir" condicional a `afericoes.create`.

**Conteúdo dos 5 itens UAT-02** — recuperar de `10-VERIFICATION.md` (git):
```bash
git show ddff1a5^:.planning/phases/10-manutencaoes/10-VERIFICATION.md
```
Itens (campo `human_verification:` do frontmatter, linhas 9-34): 1) DataTable + 5 filtros (equipamento, tipo, status, prioridade, data); 2) Criação com campos condicionais preventiva; 3) Conclusão com peças dinâmicas; 4) Aba Manutenções (tab 6); 5) Sidebar gating por `manutencoes.view`.

**Cuidado (16-RESEARCH.md Pitfall 7):** o cenário 4 esperava redação "Tolerância excedida para o equipamento"; o código atual mostra toast warn com summary "Tolerância excedida" + detail "Um ou mais parâmetros estão fora da tolerância permitida." (`VerificationFormDialog.vue:263-269`). Registrar equivalência de intenção no resultado.

### `16-PLAN.md`(s) (plan doc — verificação estruturada em ondas)

**Analog:** `15-01-PLAN.md` (estrutura) — frontmatter com `files_modified`/`must_haves`/`truths`/`artifacts`/`key_links` + seções `<read_first>`, `<objective>`, `<acceptance_criteria>`.

**Estrutura de ondas (16-RESEARCH.md "Primary recommendation"):**
- **W1 (16-01):** commit das 7 mudanças pendentes + `migrate:fresh --seed --force` no PostgreSQL real + `db:seed --force` 1x extra (idempotência BUG-01) + suíte completa verde como gate.
- **W2 (16-02):** execução dos 6 cenários UAT-01 (admin + usuários `tecnico`/`consulta`) com registro em `16-UAT.md`.
- **W3 (16-03):** execução dos 5 itens UAT-02 (admin + usuário custom sem roles) com registro e encerramento.

**Must-haves sugeridos para os planos (formato `truths:` de `15-01-PLAN.md` linhas 38-49):**
```yaml
must_haves:
  truths:
    - "Mudanças pendentes commitadas (Equipment, LoanService/MaintenanceService sync, 4 páginas Vue)"
    - "PostgreSQL real populado após migrate:fresh --seed (admin único, 6 roles, 5+5 categorias)"
    - "db:seed repetido 1x sem exceção (BUG-01 idempotência)"
    - "Suíte completa verde antes da evidência manual (165 passed / 473 assertions)"
    - "11 cenários documentados em 16-UAT.md com resultado e evidência"
  artifacts:
    - .planning/phases/16-verifica-o-uat/16-UAT.md
  key_links:
    - "16-UAT.md -> 09-UAT.md (git ddff1a5^) e 10-VERIFICATION.md (git ddff1a5^) — cenários canônicos"
```

### Commit das 7 mudanças pendentes (W1 — operação git)

**Convenção de mensagem do repo** (de `git log --format="%s"`): `feat(scope): descrição` / `fix(scope): ...` / `docs(scope): ...` / `test(scope): ...` / `chore(scope): ...` — exemplo da mensagem sugerida na pesquisa:
```bash
git add backend/app/Models/Equipment.php backend/app/Services/LoanService.php backend/app/Services/MaintenanceService.php frontend/src/modules/equipment/pages/EquipmentDetailPage.vue frontend/src/modules/equipment/pages/EquipmentFormPage.vue frontend/src/modules/equipment/pages/EquipmentListPage.vue frontend/src/modules/inventory/pages/InventoryItemFormPage.vue
git commit -m "feat: sync de status de equipamento (manutencao/emprestimo) e ajustes de frontend"
```
Arquivos: 47 inserções / 1 remoção (Equipment.php +7, LoanService.php +16, MaintenanceService.php +18, 4 páginas Vue). Commitar **antes** de registrar evidência (regra CONTEXT.md "tudo versionado"; Pitfall 8).

### Evidência automatizada (gate e por onda)

**Comandos padrão (de `16-VALIDATION.md` linhas 22-23 e `16-RESEARCH.md` "Code Examples"):**
```bash
# Gate W1 — suíte completa
docker compose -f docker/docker-compose.yml exec -T php php artisan test          # 165 passed / 473 assertions (meta)

# Por onda — suítes de apoio (evidência automatizada dos cenários)
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=VerificationUatFixTest      # 5 tests / 28 assertions (UAT-01)
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=MaintenanceVerificationTest # 6 tests / 23 assertions (UAT-02)
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=RbacRegressionTest          # 14 tests / 23 assertions (UAT-01 #5/#6, UAT-02 #5)
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=SeederIdempotencyTest       # 2 tests / 14 assertions (UAT-01 #1, BUG-01)
```
Cada resultado automatizado vira um item `source: automated` no 16-UAT.md com a ref do teste no `expected` (padrão `15-UAT.md` itens 3-12).

### Criação de usuários de teste (cenários de permissão)

**Padrão tinker** (de `16-RESEARCH.md` "Code Examples" — usados nos cenários 5/6 do UAT-01 e item 5 do UAT-02; **nenhum role seedado deixa de ter `manutencoes.view`** → caso negativo do item 5 exige usuário SEM roles):
```bash
# UAT-01 #5 (sem afericoes.view → aba Aferições oculta): role tecnico
docker compose -f docker/docker-compose.yml exec -T php php artisan tinker --execute="
  \$u = App\Models\User::factory()->create(['name' => 'Tecnico UAT', 'email' => 'tecnico@uat.test', 'password' => bcrypt('senha123')]);
  \$u->roles()->attach(App\Models\Role::where('slug', 'tecnico')->value('id')); echo 'ok';"
# UAT-01 #6 (afericoes.view sim, afericoes.create não → botão Aferir oculto): role consulta
# UAT-02 #5 (sem manutencoes.view → item sidebar oculto): usuário SEM roles (cria via factory sem attach)
```
Fonte: [VERIFIED: repository — RolePermissionSeeder.php, UserFactory.php]

---

## Shared Patterns

### Formato de registro UAT (XX-UAT.md)
**Source:** `15-UAT.md` (linhas 1-98) + `09-UAT.md` (linhas 1-75, via git)
**Apply to:** `16-UAT.md`
- Frontmatter YAML com `status: testing` / `phase` / `source` / `started` / `updated`
- `Current Test` com `number` / `name` / `expected` (multilinha `|`) / `awaiting: user response`
- Lista `Tests` com `expected` / `result` / `source` / `coverage_id`
- `Summary` (total/passed/issues/pending/skipped/blocked) + `Gaps`
- A mecânica é conversacional: `/gsd-verify-work 16` — o executor responde cada `Current Test`, o agente registra o resultado

### Evidência por cenário
**Source:** `15-UAT.md` itens 3-12 (automatizados com `coverage_id` D1-D5); `09-UAT.md` (manuais com `result: [pending]`)
**Apply to:** todos os 11 cenários do 16-UAT.md
- `source: automated` + comando `--filter=` documentado no `expected` ⇒ ref de teste
- `source: manual` + descrição do que foi executado/observado (prints quando útil)
- **Nunca registrar aprovado sem evidência** (Anti-Pattern 1): cada cenário precisa de resultado + descrição + (se reprovado) erro exato

### Gate de suíte antes de evidência manual
**Source:** `15-VERIFICATION.md` linha 39 (truth #10) / `16-RESEARCH.md` Pattern 2
**Apply to:** todos os cenários com cobertura automatizada (os 11 têm)
- Suíte completa verde (165 passed / 473 assertions) antes da execução manual
- Rodar `--filter=` por onda como prova contínua

### Caminho funcional da UI (não se desviar)
**Source:** `16-RESEARCH.md` Pitfall 2 / Environment Availability
**Apply to:** toda a execução manual
- **Sempre** `http://localhost:5173` (vite dev server, proxy `/api` → :80) — `http://localhost` (nginx) retorna 500 (dist não montado/stale)
- Login admin: `admin@labcontrol.com` / `@dmin123` (AdminUserSeeder)
- Rate limit: 5 falhas → 429/60s ("Muitas tentativas. Aguarde 1 minuto.") — usar sempre credenciais corretas

### RBAC enforcement (contexto dos cenários 5/6 e item 5)
**Source:** `15-01` (b645be7, 7a45b8f) — 14 controllers `implements HasMiddleware` + `new Middleware('permission:x', ...)`; prova em `RbacRegressionTest` (403 real)
**Apply to:** interpretação dos resultados — gating visual (`v-if hasPermission`) é UX; a barreira de verdade é o middleware → 403. Usuário SEM role recebe 403 em TODOS os módulos.

---

## No Analog Found

| File | Role | Data Flow | Reason |
|------|------|-----------|--------|
| — (nenhum) | — | — | Todos os arquivos da fase têm analog exato no repo: 16-UAT.md ← 15/09/10-UAT, planos ← 15-01-PLAN, evidência ← suítes existentes. Os 7 arquivos commitados já existem (só git add/commit). |

## Metadata

**Analog search scope:** `.planning/phases/15-corre-es-de-funcionamento/` (15-UAT.md, 15-VALIDATION.md, 15-VERIFICATION.md, 15-01/15-02-PLAN.md, 15-RESEARCH.md), git history `ddff1a5^` (09-UAT.md, 09-VERIFICATION.md, 09-VALIDATION.md, 10-VERIFICATION.md, 10-VALIDATION.md), `backend/tests/Feature/` (VerificationUatFixTest, MaintenanceVerificationTest, SeederIdempotencyTest), `.planning/phases/16-verifica-o-uat/` (16-VALIDATION.md), working tree (git status/diff dos 7 arquivos)
**Files scanned:** 12 (5 novos/commitados analisados + 4 suítes de apoio + 3 artefatos 16/15)
**Pattern extraction date:** 2026-08-09
