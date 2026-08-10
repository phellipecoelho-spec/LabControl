# Phase 16: Verificação UAT - Research

**Researched:** 2026-08-09
**Domain:** Execução e verificação de UAT (Laravel 13 API + Vue 3/PrimeVue SPA)
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

Decisões travadas no CONTEXT.md raiz do projeto (válidas para todas as fases):

- **Frontend:** Vue 3 + Vite + TypeScript + PrimeVue + Pinia + Vue Router. Visual profissional (referências Power BI/Azure/Notion/GitHub/Linear/ClickUp), **tema escuro moderno**.
- **Backend:** Laravel (API REST) com autenticação via Sanctum. Tudo via API — nunca acesso direto ao banco.
- **Banco de Dados:** PostgreSQL. **Redis** para cache e filas. Armazenamento de arquivos no Storage do Laravel (local ou S3).
- **Gráficos:** Apache ECharts (não Chart.js).
- **Containerização:** Docker Compose para desenvolvimento e implantação.
- **Controle de versão:** Git + GitHub. **Documentação da API:** OpenAPI/Swagger.
- **Execução local e online:** mesma arquitetura, alterando apenas configurações de ambiente.
- **Regra de qualidade (projeto inteiro):** "Nenhuma entrega será um 'exemplo de código'. Todas serão código de produção, documentado, testável e versionado."
- **Roadmap em módulos/sprints** — cada Sprint gera uma nova versão; sistema modular (equipamentos, estoque, metrologia, qualidade, documentos, usuários, dashboards), preparado para multiempresa/multilaboratório no futuro.
- **App mobile futuro:** Capacitor sobre o Vue (PWA já habilitado). Funcionamento offline via PWA com sincronização.

### the agent's Discretion

Não há fase-CONTEXT.md (16-CONTEXT.md) — o único CONTEXT.md do projeto é o raiz. Os cenários UAT de Aferições/Manutenções e a mecânica de execução estão definidos nos artefatos das fases 09/10/15 (documentados abaixo) e na convenção XX-UAT.md + `/gsd-verify-work`.

### Deferred Ideas (OUT OF SCOPE)

- Aplicativo mobile nativo (PWA suficiente para v1.x; Capacitor no futuro).
- Testes E2E (Playwright) — **Fase 18**, não nesta fase.
- Revisão de layout/tema escuro/responsividade — **Fase 19**.
- Indicadores PWA offline/sync e alertas de estoque mínimo — **Fase 20**.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| UAT-01 | Usuário pode executar os 6 cenários UAT de Aferições (Fase 09) sem falhas ou bloqueios | Os 6 cenários foram recuperados de `09-UAT.md` (git history, commit ddff1a5^). Componentes frontend existem e estão wiringados (VerificationPendingPage, VerificationFormDialog, VerificationHistoryTab, aba tab 3 do EquipmentDetailPage). Backend: rotas canônicas `/api/v1/verifications/pending` + `/api/v1/equipments/{id}/verifications`; RBAC `afericoes.view/create` enforcement verificado (403 real). Suíte automatizada de apoio verde (VerificationUatFixTest 5/28). |
| UAT-02 | Usuário pode executar os 5 itens visuais UAT de Manutenções (Fase 10) sem falhas ou bloqueios | Os 5 itens foram recuperados de `10-VERIFICATION.md` ("Manual Verification Required — 5 items") + `10-VALIDATION.md` (Manual-Only). Componentes existem (MaintenanceListPage com 5 filtros, MaintenanceOpenDialog com campos condicionais preventiva, MaintenanceCloseDialog com peças dinâmicas, MaintenanceHistoryTab tab 6, sidebar navigation.ts). Backend: `MaintenanceOrderController::index` suporta os 5 filtros; rotas canônicas `/api/v1/equipments/{id}/maintenance`; RBAC `manutencoes.*`. Suíte de apoio verde (MaintenanceVerificationTest 6/23). |
| Sucesso #3 | Resultado de cada cenário documentado (aprovado/reprovado) com evidência | Convenção estabelecida: arquivo `16-UAT.md` no formato das fases anteriores (frontmatter status/phase/source; Current Test; lista Tests com expected/result/source/coverage_id; Summary; Gaps). Evidência por cenário: execução manual na UI (screenshots/descrição) + testes automatizados de apoio (`php artisan test --filter=...`). |
</phase_requirements>

## Summary

A Fase 16 é uma **fase de verificação manual com apoio automatizado** — não instala pacotes, não cria features. O objetivo é executar os 11 cenários UAT pendentes do v1.0 (6 de Aferições — Fase 09, 5 visuais de Manutenções — Fase 10) na UI real, após as correções da Fase 15 (RBAC enforcement, seeders idempotentes, rotas canônicas, relatórios, rate limit), e documentar o resultado de cada um com evidência.

Os documentos 09-UAT.md e 10-VERIFICATION.md foram **arquivados do working tree** pelo commit `ddff1a5` (limpeza dos diretórios v1.0) — mas estão integralmente recuperáveis do git history e são a fonte canônica dos cenários. A pesquisa recuperou os 6 cenários de Aferições (09-UAT.md) e os 5 itens visuais de Manutenções (10-VERIFICATION.md "Manual Verification Required") e confirmou, por inspeção do código atual (frontend + backend) e execução de testes, que **todo o código necessário para os 11 cenários já existe e está wiringado** — a Fase 15 corrigiu exatamente os bloqueios que impediam esses fluxos (RBAC falso-bypass → 403 reais, rotas legadas inexistentes → rotas canônicas, seeders não idempotentes → BUG-01).

**Estado do ambiente é o principal risco da fase:** (1) o banco PostgreSQL real está **vazio** (0 usuários, 0 roles, 0 equipamentos — confirmado via tinker) — precisa `migrate:fresh --seed` antes de qualquer UAT, o que também liquida o Manual-Only pendente da Fase 15; (2) a UI **deve ser acessada via vite dev server em http://localhost:5173** — o nginx (`http://localhost`) retorna 500 para rotas SPA porque o `frontend/dist` não está montado no container e está desatualizado (build de 27/07); (3) a suíte backend está verde (VerificationUatFixTest 5 passed/28 assertions e MaintenanceVerificationTest 6 passed/23 assertions re-executados hoje), mas o `npm run typecheck` ainda reporta **7 erros pré-existentes** (deferred-items.md da Fase 15) que bloqueiam `npm run build` — não bloqueiam o dev server. Há também **mudanças não commitadas na working tree** (sync de status de equipamento em MaintenanceService/LoanService, relation `loans` no Equipment, 4 páginas Vue) que são servidas pelo dev server via HMR e afetam diretamente os fluxos do UAT-02 — devem ser commitadas antes de registrar evidência.

**Primary recommendation:** Estruturar a fase como 3 ondas: (W1) preparação do ambiente — commit das mudanças pendentes + `migrate:fresh --seed` no PostgreSQL real + verificação da UI no dev server + suíte completa verde como gate; (W2) execução dos 6 cenários UAT-01 (Aferições) com usuário admin + usuários de permissão (tecnico/consulta) e documentação em 16-UAT.md; (W3) execução dos 5 itens UAT-02 (Manutenções) com documentação e encerramento. Cada cenário registrado aprovado/reprovado com evidência (descrição da execução + estado observado; prints quando útil).

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Execução dos cenários UAT (navegação, formulários, permissões visuais) | Browser / Client | — | São walkthroughs visuais/interativos nas páginas Vue + PrimeVue (DataTables, dialogs, toasts, tabs). A validação de rendering condicional (abas/botões por permissão) só existe no cliente. |
| Contrato da API por trás dos cenários (store, pending, history, RBAC 403/200) | API / Backend | — | Laravel controllers, rotas canônicas e middleware de permissão. A prova automatizada do contrato vive na suíte PHPUnit (VerificationUatFixTest, MaintenanceVerificationTest, RbacRegressionTest). |
| Dados de base para UAT (equipamentos, templates, aferições, ordens) | Database / Storage | API / Backend | PostgreSQL via Docker. Seeders idempotentes (Fase 15) populam o estado inicial; a API expõe. Nenhum UAT pode rodar com banco vazio. |
| Registro de evidência e status dos cenários | Frontend Server (SSR) | — | N/A — o registro é documental (16-UAT.md), não há tier de servidor envolvido; fica sob responsabilidade do processo GSD (verification artifacts). |
| Sessão de login para execução (Sanctum SPA, CSRF, rate limit) | API / Backend | Browser / Client | AuthController + sessão Redis + cookies HttpOnly; o cliente só consome. Rate limit (5 falhas → 429/60s) governa a interação manual. |

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel (API) | 13.20.0 | Backend REST + Sanctum + RBAC custom | Já travado no projeto; 165 testes verdes; toda a API de aferições/manutenções implementada |
| PHPUnit | ^12.5.12 | Suíte de apoio automatizada | É o framework de teste da Fase 15 (15-VALIDATION.md); comandos `php artisan test --filter=` já documentados |
| Vue 3 + Vite + TypeScript + PrimeVue 5 | ^3.5.40 / ^8 / ^5 / ^5.0.0 | UI dos cenários UAT | Stack travada; páginas e componentes dos 11 cenários existem |
| Docker Compose | — | Ambiente local (php, postgres, redis, nginx) | 4 containers healthy; comandos `docker compose -f docker/docker-compose.yml exec -T php ...` são o padrão da Fase 15 |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `php artisan tinker` | (Laravel) | Criar usuários de teste com roles específicas | Para cenários de permissão (UAT-01 #5/#6, UAT-02 #5) quando não houver usuário seedado com o perfil certo |
| `npm run dev` (vite) | 8.1.5 | Servir a UI no dev server (:5173) com proxy `/api` → :80 | É o ÚNICO caminho funcional para a UI (nginx/dist quebrado) |
| `npm run typecheck` | vue-tsc 2.2.2 | Verificação de tipos do frontend | OPCIONAL/INFORMATIVO — 7 erros pré-existentes conhecidos; não bloqueia dev server |
| `git show ddff1a5^:...` | — | Recuperar 09-UAT.md / 10-VERIFICATION.md do history | Fonte canônica dos cenários (arquivos arquivados no commit de limpeza v1.0) |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Executar UI via `npm run dev` (:5173) | Build + nginx (http://localhost) | nginx NÃO monta `frontend/dist` no container (docker-compose.yml) e o dist é de 27/07 — `http://localhost/` retorna 500 hoje. Dev server é o caminho verificado. |
| Criar usuários de permissão via tinker | Usuários seedados (não existem não-admin) | Não há seeder de usuários demo — só AdminUserSeeder. Tinker (ou a tela admin de usuários) é o caminho. |
| Documentar UAT em formato livre | Convenção XX-UAT.md (09-UAT.md/15-UAT.md) | A convenção já é o padrão do projeto e alimenta `/gsd-verify-work`; formato livre quebraria a rastreabilidade. |

**Installation:** Nenhum — fase de verificação pura. Nenhum pacote novo.

**Version verification:** Nenhum pacote novo a verificar. Versões de stack confirmadas nos arquivos do repo: `composer.json`/`composer.lock` (Laravel 13.20.0, PHPUnit ^12.5.12, barryvdh/laravel-dompdf 3.1.2, maatwebsite/excel 3.1.69) e `frontend/package.json` (vue ^3.5.40, primevue ^5.0.0, vite 8.1.5).

## Package Legitimacy Audit

> **N/A — esta fase não instala pacotes externos** (verificação-only). Não há novos `npm install`/`composer require` no escopo. As dependências da stack já foram auditadas nas fases anteriores (15-01 instalou dompdf/excel com checkpoint human-verify).

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| — | — | — | — | — | — | Nenhum pacote nesta fase |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

## Architecture Patterns

### System Architecture Diagram

```
Usuário (UAT executor)
        │  navega no browser
        ▼
┌─────────────────────────────┐   /api/* (proxy vite)   ┌──────────────────────────────┐
│  Vite dev server (:5173)    │ ──────────────────────► │  nginx (:80) → php:9000      │
│  Vue 3 SPA + PrimeVue       │                         │  Laravel 13 API (Sanctum)    │
│  (verifications/maintenance)│ ◄────────────────────── │  RBAC permission middleware  │
└─────────────────────────────┘        JSON + cookies   └──────────────┬───────────────┘
                                                                       │
                                              ┌────────────────────────┴───────────────┐
                                              ▼                                        ▼
                                  ┌─────────────────────────┐            ┌──────────────────────┐
                                  │  PostgreSQL (labcontrol) │            │  Redis (sessão,     │
                                  │  seeders idempotentes    │            │  cache, rate limit) │
                                  └─────────────────────────┘            └──────────────────────┘
                                                                      
Fluxo UAT-01: Login admin (:5173) → Sidebar Operações → Aferições (/verifications, GET pending)
  → DataTable pendentes → Dialog "Nova Aferição" (GET templates por equipamento) → POST /api/v1/verifications
  → toast sucesso/warn → aba Aferições (tab 3) no /equipments/:id (GET /equipments/{id}/verifications)
Fluxo UAT-02: Sidebar Operações → Manutenções (/maintenance, GET com 5 filtros) → Nova Manutenção (POST /maintenance-orders)
  → Concluir (POST /maintenance-orders/{id}/complete com parts) → aba Manutenções (tab 6) no /equipments/:id
```

**Pontos de decisão:** (1) Renderização condicional por permissão (`v-if authStore.hasPermission(...)` nas abas 3/6 e botões Aferir/Nova Manutenção) — decide o que o executor vê; (2) servidor devolve 403 para usuário sem permissão (RBAC Fase 15) — segunda barreira de verdade; (3) rate limit no login (5 falhas → 429) — limita tentativas erradas repetidas.

### Recommended Project Structure

A estrutura do projeto **não muda** nesta fase (verificação-only). Arquivos tocados:

```
.planning/phases/16-verifica-o-uat/
├── 16-RESEARCH.md          # este documento
├── 16-PLAN.md(s)           # planos de verificação (planner)
└── 16-UAT.md               # RESULTADO: 11 cenários com status aprovado/reprovado + evidência
```

Código consumido (só leitura/smoke, sem alteração):
```
frontend/src/modules/verifications/  (PendingPage, FormDialog, HistoryTab)
frontend/src/modules/maintenance/    (ListPage, OpenDialog, CloseDialog, HistoryTab, DetailPage)
frontend/src/modules/equipment/pages/EquipmentDetailPage.vue  (abas 3 e 6)
backend/app/Services/VerificationService.php  (getPendingVerifications — bug Carbon/DB::raw já corrigido no commit 022c30b)
backend/app/Services/MaintenanceService.php  (sync de status de equipamento — NA WORKING TREE, não commitado)
backend/tests/Feature/VerificationUatFixTest.php / MaintenanceVerificationTest.php / RbacRegressionTest.php
```

### Pattern 1: Documentação de UAT no formato XX-UAT.md

**What:** Frontmatter YAML (`status`, `phase`, `source`, `started`, `updated`) + seção `Current Test` + lista `Tests` (cada item com `expected`, `result`, `source: automated|manual`, `coverage_id`) + `Summary` (total/passed/issues/pending/skipped/blocked) + `Gaps`.
**When to use:** Toda execução de UAT conversacional (`/gsd-verify-work`) e registro de evidência por cenário.
**Example:** [VERIFIED: repository — .planning/phases/15-corre-es-de-funcionamento/15-UAT.md; .planning/phases/09-afericoes/09-UAT.md via git show ddff1a5^]

```yaml
---
status: testing
phase: 16-verifica-o-uat
source: 16-01-SUMMARY.md
started: <data>
updated: <data>
---

## Current Test
number: 1
name: DataTable de Aferições Pendentes — layout, loading e estado vazio
expected: |
  Página `/verifications` carrega DataTable com colunas (Equipamento, Patrimônio, Nº Série, Categoria, Frequência, Última Aferição, Ações).
  Loading skeleton durante carregamento. Estado vazio "Todos os equipamentos estão em dia" quando sem pendentes.
awaiting: user response

## Tests
### 1. DataTable de Aferições Pendentes — layout, loading e estado vazio
expected: <texto do cenário>
result: pass          # ou: fail (com motivo) | pending
source: manual        # ou: automated (com ref de teste)
coverage_id: UAT-01-S1
```

### Pattern 2: Gate de suíte antes da evidência manual

**What:** Antes de iniciar a execução manual dos cenários, rodar a suíte completa como prova automatizada de apoio e registrar o resultado como evidência de que os fluxos de API por trás de cada cenário respondem conforme contrato.
**When to use:** Sempre que um cenário manual tiver cobertura automatizada correspondente (os 11 têm: VerificationUatFixTest 5, MaintenanceVerificationTest 6 + RbacRegressionTest 14).
**Example:** [VERIFIED: repository — 15-VERIFICATION.md "Suite completa verde ao fim do wave (gate antes de /gsd-verify-work)"]

### Anti-Patterns to Avoid

- **Registrar UAT como aprovado sem evidência:** cada cenário deve ter resultado + descrição do que foi executado/observado. Se reprovado, registrar o erro exato (console, status HTTP, screenshot).
- **Executar UAT contra o nginx (:80):** a SPA não é servida lá (500). Sempre :5173 (dev server) — [VERIFIED: runtime probe, http://localhost/ → 500].
- **Assumir banco populado:** o PostgreSQL real está vazio — sem `migrate:fresh --seed` o UAT falha com "sem dados". [VERIFIED: tinker counts = 0]
- **Tentar logar errado 5+ vezes seguidas:** rate limit bloqueia 60s (429) e atrapalha o fluxo da sessão de UAT.
- **Rodar cenário de permissão com usuário sem role:** com o RBAC da Fase 15, usuário sem role recebe 403 em TODOS os módulos — os usuários de teste devem ter roles anexadas (via admin ou tinker).
- **Esquecer as mudanças não commitadas:** o sync de status de equipamento (MaintenanceService/LoanService) e as 4 páginas Vue alteradas estão na working tree; o dev server já as serve. Commit antes de registrar evidência para que a evidência corresponda a código versionado (regra do CONTEXT.md: tudo versionado).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Checklist/formato de UAT | Formato próprio de checklist | Convenção XX-UAT.md + `/gsd-verify-work` | Formato já consumido pelo processo GSD (15-UAT.md, 09-UAT.md); rastreabilidade coverage_id→requisito |
| Rodar testes backend | Comandos ad-hoc | `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<X>` | Padrão da Fase 15 (15-VALIDATION.md); 165 testes/473 assertions |
| Provar RBAC/permissões | Teste manual de cada endpoint | RbacRegressionTest (14 testes) + VerificationUatFixTest/MaintenanceVerificationTest | Já existem e estão verdes; o UAT manual complementa o visual, não substitui |
| Popular dados para UAT | Inserções manuais no banco | Seeders idempotentes (`migrate:fresh --seed`) | Seeders da Fase 15: admin/roles/permissões + 6 seeders de dados sem duplicação |
| Recuperar cenários das fases 09/10 | Recriar os cenários de memória | `git show ddff1a5^:.planning/phases/09-afericoes/09-UAT.md` e `10-VERIFICATION.md` | Fonte canônica; evita inventar critérios divergentes do contrato original |

**Key insight:** esta fase não deve escrever nenhum código de produto. Tudo o que precisa existir já existe e está verde; o trabalho é **executar, observar e documentar** com as ferramentas de verificação que o projeto já possui.

## Common Pitfalls

### Pitfall 1: Banco PostgreSQL real vazio
**What goes wrong:** UAT-01/UAT-02 não têm dados para exibir (lista de pendentes vazia, sem equipamentos/ordens para abrir).
**Why it happens:** A validação automatizada usa sqlite :memory: (phpunit.xml); o banco real nunca foi seedado nesta instalação (0 users/roles/equipments — verificado via tinker). O Manual-Only da Fase 15 (seed 2x no PostgreSQL + login admin) ficou pendente.
**How to avoid:** Primeira tarefa do plano: `docker compose -f docker/docker-compose.yml exec -T php php artisan migrate:fresh --seed --force` (banco vazio ⇒ operação segura) e, como item de evidência, repetir o `db:seed` 1x extra sem exceção (idempotência BUG-01). Isso liquida o Manual-Only pendente da Fase 15.
**Warning signs:** login admin falha com "credenciais inválidas" (usuário não existe); `/verifications` mostra "Todos os equipamentos estão em dia" mesmo sem dados reais.

### Pitfall 2: Acessar a UI pelo caminho errado (localhost:80)
**What goes wrong:** `http://localhost/` retorna **500** para a SPA; o executor não consegue navegar.
**Why it happens:** `docker/nginx/default.conf` serve o frontend de `/var/www/frontend/dist`, mas o `docker-compose.yml` NÃO monta `frontend/dist` no container nginx (só monta `../backend`), e o dist local é antigo (build de 27/07/2026).
**How to avoid:** Usar o dev server `npm run dev` (já rodando em http://localhost:5173; proxy `/api` → :80 já configurado no vite.config.ts; `FRONTEND_URL=http://localhost:5173` no .env). Se o dev server não estiver de pé, iniciá-lo (`npm run dev` em frontend/).
**Warning signs:** curl/abrir :80 → 500; assets não carregam.

### Pitfall 3: Rate limit de login (5 falhas → 429 por 60s)
**What goes wrong:** Durante testes de permissão, digitar credenciais erradas 5x bloqueia o IP por 60s com "Muitas tentativas. Aguarde 1 minuto." (mensagem PT-BR).
**Why it happens:** Contrato da Fase 15: apenas tentativas falhas contam; sucesso limpa o contador (RateLimitTest).
**How to avoid:** Usar sempre credenciais corretas (admin@labcontrol.com / @dmin123 — AdminUserSeeder); anotar as credenciais dos usuários de teste criados. Se bloquear, aguardar 60s.
**Warning signs:** login retorna 429.

### Pitfall 4: Cenários de permissão com usuário inadequado
**What goes wrong:** O cenário "usuário sem permissão X não vê Y" falha porque o usuário de teste tem permissão demais (ou de menos) ou recebe 403 inesperado.
**Why it happens:** RBAC ativo (Fase 15): usuário sem role → 403 em todos os módulos. Perfis seedados: `tecnico` (tem manutencoes.view, NÃO tem afericoes.view/create), `consulta` (tem afericoes.view, NÃO tem afericoes.create), `laboratorista` (operação completa). **Nenhum role seedado deixa de ter manutencoes.view** — o caso negativo do item 5 do UAT-02 exige usuário custom (sem roles ou role mínima).
**How to avoid:** Criar usuários de teste via tinker com as roles certas:
```bash
docker compose -f docker/docker-compose.yml exec -T php php artisan tinker --execute="
  \$u = App\Models\User::factory()->create(['name' => 'Tecnico UAT', 'email' => 'tecnico@uat.test', 'password' => bcrypt('senha123')]);
  \$u->roles()->attach(App\Models\Role::where('slug', 'tecnico')->value('id'));"
```
**Warning signs:** aba/botão visível quando deveria estar oculto (ou 403 quando deveria ver).
**Nota:** a numeração de abas esperada (Aferições=3, Arquivos=4, Logs=5, Manutenções=6) está correta no EquipmentDetailPage atual [VERIFIED: repository].

### Pitfall 5: Typecheck frontend com 7 erros pré-existentes
**What goes wrong:** `npm run typecheck` falha (7 erros) e `npm run build` (= `vue-tsc && vite build`) não gera dist.
**Why it happens:** Erros pré-existentes fora do escopo do 15-02: PasswordInput.vue:9, EquipmentLogsSection.vue:96, LoanCreateDialog.vue:61/79/264/267, router/index.ts:29 — registrados em `deferred-items.md` da Fase 15. Não tocam as páginas de aferições/manutenções.
**How to avoid:** NÃO incluir build/typecheck como gate obrigatório desta fase (dev server funciona sem typecheck). Registrar como evidência opcional; remeter correção à Fase 19 (Layout) se desejado. Se o plano exigir build para screenshot estático, corrigir os 7 erros vira escopo extra — decidir com o usuário.
**Warning signs:** `npm run build` aborta com erros TS2345/TS2322/TS2358/TS2339.

### Pitfall 6: Estado vazio da lista de pendentes (UAT-01 #1)
**What goes wrong:** O cenário pede verificar o estado vazio "Todos os equipamentos estão em dia", mas após o seed a lista vem populada.
**Why it happens:** O seed cria equipamentos com `verification_frequency` e alguns sem aferição recente → pendentes reais.
**How to avoid:** Verificar o estado vazio como *consequência do fluxo*: após aferir todos os pendentes (ou após criar equipamento com frequência e aferi-lo), a lista esvazia e o EmptyState aparece. Registrar as duas observações (lista populada + vazio após aferição) como evidência do cenário.
**Warning signs:** executor marca o cenário como "reprovado" por não ver o vazio de primeira.

### Pitfall 7: Wording do toast de tolerância difere do texto do UAT original
**What goes wrong:** O cenário 4 espera mensagem "Tolerância excedida para o equipamento"; o código atual mostra toast `warn` com summary **"Tolerância excedida"** e detail "Um ou mais parâmetros estão fora da tolerância permitida." (VerificationFormDialog.vue:263-269).
**Why it happens:** Texto ajustado nas fases posteriores; o cenário da Fase 09 usava redação antiga.
**How to avoid:** Aceitar equivalência de redação (a intenção — aviso warn de tolerância excedida — está presente). Anotar a divergência no resultado do cenário se o executor quiser rigor textual.
**Warning signs:** reprovação por diferença cosmética de texto.

### Pitfall 8: Evidência de UAT vs código não versionado
**What goes wrong:** Cenários executados sobre código que ainda não foi commitado (working tree) — a evidência não corresponde a um commit.
**Why it happens:** Há mudanças pendentes da sessão anterior (Equipment model, LoanService/MaintenanceService status sync, 4 páginas Vue) — o dev server já as serve via HMR.
**How to avoid:** Commit das mudanças pendentes na W1 (antes da execução) — alinhado à regra do CONTEXT.md ("tudo versionado"). Se o usuário preferir não commitar ainda, registrar no 16-UAT.md que a execução incluiu mudanças não commitadas.
**Warning signs:** `git status` mostra arquivos modificados durante toda a fase.

## Code Examples

Padrões verificados (fontes do próprio repositório):

### Preparação do ambiente (W1)
```bash
# 1) Commit das mudanças pendentes (sync de status, relation loans, páginas Vue)
git add backend/app/Services/MaintenanceService.php backend/app/Services/LoanService.php backend/app/Models/Equipment.php frontend/src/modules/equipment/pages/EquipmentDetailPage.vue frontend/src/modules/equipment/pages/EquipmentFormPage.vue frontend/src/modules/equipment/pages/EquipmentListPage.vue frontend/src/modules/inventory/pages/InventoryItemFormPage.vue
git commit -m "feat: sync de status de equipamento (manutencao/emprestimo) e ajustes de frontend"

# 2) Seed do PostgreSQL real (banco vazio — operação segura; liquida Manual-Only da Fase 15)
docker compose -f docker/docker-compose.yml exec -T php php artisan migrate:fresh --seed --force
# 2b) Prova de idempotência (BUG-01): repetir db:seed sem --fresh
docker compose -f docker/docker-compose.yml exec -T php php artisan db:seed --force

# 3) Gate automatizado — suíte completa
docker compose -f docker/docker-compose.yml exec -T php php artisan test          # 165 passed / 473 assertions (meta)
```

### Suítes de apoio por módulo (evidência automatizada dos cenários)
```bash
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=VerificationUatFixTest      # 5 tests / 28 assertions
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=MaintenanceVerificationTest # 6 tests / 23 assertions
docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=RbacRegressionTest          # 14 tests / 23 assertions
```
Fonte: [VERIFIED: repository — 15-VALIDATION.md, 15-VERIFICATION.md; re-executado hoje: VerificationUatFixTest 5 passed/28 assertions, MaintenanceVerificationTest 6 passed/23 assertions]

### Criação de usuários de teste para cenários de permissão
```bash
# UAT-01 #5 (sem afericoes.view → aba Aferições oculta): role tecnico
docker compose -f docker/docker-compose.yml exec -T php php artisan tinker --execute="
  \$u = App\Models\User::factory()->create(['name' => 'Tecnico UAT', 'email' => 'tecnico@uat.test', 'password' => bcrypt('senha123')]);
  \$u->roles()->attach(App\Models\Role::where('slug', 'tecnico')->value('id')); echo 'ok';"
# UAT-01 #6 (afericoes.view sim, afericoes.create não → botão Aferir oculto): role consulta
# UAT-02 #5 (sem manutencoes.view → item do sidebar oculto): usuário SEM roles ou role mínima custom
```
Fonte: [VERIFIED: repository — RolePermissionSeeder.php roles/permissions; UserFactory.php]

### Login manual na UI
1. Abrir `http://localhost:5173` (dev server) — se não estiver rodando: `npm run dev` em `frontend/`.
2. Logar com `admin@labcontrol.com` / `@dmin123` (AdminUserSeeder) [VERIFIED: repository].
3. Rotas: `/verifications` (Aferições Pendentes), `/maintenance` (Manutenções), `/equipments/:id` (abas 3 e 6), sidebar Operações → Aferições/Manutenções.

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Rotas legadas `/verifications/by-equipment/{id}` e `/maintenance-orders/by-equipment/{id}` | Rotas canônicas `/api/v1/equipments/{id}/verifications` e `/api/v1/equipments/{id}/maintenance` | Fase 15-02 (commit 9ae0e9b) | Testes e frontend usam o contrato real; rotas legadas inexistem (403/404 se usadas) |
| RBAC não aplicado (controllers sem HasMiddleware → bypass) | `implements HasMiddleware` + `new Middleware('permission:x', only: [...])` em 14 controllers + RoleController com roles.manage | Fase 15-01 (b645be7, 7a45b8f) | Usuário sem permissão recebe 403 real — os cenários 5/6 do UAT-01 e o item 5 do UAT-02 agora têm enforcement server-side |
| Seeders não idempotentes (duplicavam em re-seed) | firstOrCreate em colunas UNIQUE + guards de count | Fase 15-02 (b81ca85) | `migrate:fresh --seed`/`db:seed` repetível sem exceção (BUG-01) |
| Query de pendentes com `subHours(DB::raw())` (Carbon misturado com SQL raw) | Thresholds Carbon por frequência (`now()->subDay()` etc.) | Fase 09 (commit 022c30b) | Lista de pendentes correta; UAT-01 #1 confiável |
| dompdf API v2 (`setOption(array)`, `StreamedResponse`) | dompdf v3 (`setOptions()`, `Illuminate\Http\Response`) | Fase 15-01 (7a45b8f) | Relatórios sem 500 (não é alvo desta fase, mas garante que o ambiente está são) |

**Deprecated/outdated:**
- `throttle:auth` no POST /auth/login: removido (Fase 15-01) — rate limit agora é check-após-falha no controller (429 com mensagem PT, sucesso limpa).
- `assignRole()`/Spatie: projeto usa RBAC custom (`roles()->attach` + `hasPermission`) — nenhum teste/fixture deve importar Spatie (Fase 15-02).

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Os "5 itens visuais UAT de Manutenções" (UAT-02) = os 5 itens de "Manual Verification Required" da 10-VERIFICATION.md (DataTable+filtros, criação com campos condicionais, conclusão com peças, aba tab 6, sidebar gating) | Phase Requirements / Summary | Baixo — triangulado com 10-VALIDATION.md (Manual-Only Verifications) e STATE.md ("Phase 10 — human_needed (5 visual items)"). Não existia 10-UAT.md no repo. |
| A2 | O mecanismo de execução do UAT é conversacional (`/gsd-verify-work`) com registro em 16-UAT.md | Patterns | Baixo — convenção das fases 09 (09-UAT.md "awaiting: user response") e 15 (15-UAT.md). Se o usuário preferir outra mecânica, o plano ajusta o formato de evidência. |
| A3 | Os 6 cenários UAT-01 são exatamente os do 09-UAT.md recuperado do git (nenhuma revisão posterior) | Summary | Baixo — 09-VERIFICATION.md lista os mesmos 6 itens como "Human Verification Required". |
| A4 | Os 7 erros de typecheck continuam sendo os únicos do frontend (nenhum novo introduzido pelas mudanças da working tree) | Common Pitfalls | Médio — re-executado hoje: exatamente os 7 (mesmos arquivos). As páginas novas passam limpas. |
| A5 | `npm run dev` continua sendo o único caminho funcional para a UI (nginx/dist sem volume) | Environment | Baixo — confirmado por probe (localhost:80 → 500; dist sem mount no compose). Se o usuário adicionar volume/rebuild, o caminho muda. |
| A6 | A suíte completa segue em 165 passed / 473 assertions com as mudanças da working tree | Validation Architecture | Médio — os 2 arquivos de apoio + RbacRegressionTest foram re-executados verdes hoje; o número total vem da 15-VERIFICATION.md (mesmo dia). Gate da fase re-roda a suíte completa. |
| A7 | Usuário UAT participa ativamente da execução (cenários são "Usuário executa...") | Summary | Baixo — os critérios de sucesso UAT-01/UAT-02 dizem "Usuário executa"; a fase é human-in-the-loop por definição. |

## Open Questions

1. **Mudanças não commitadas da working tree — commitar na W1?**
   - What we know: 8 arquivos modificados (Equipment model, LoanService, MaintenanceService — sync de status; 4 páginas Vue). Afetam diretamente UAT-02 (status de equipamento "Manutenção"/"Ativo" na página de detalhe).
   - What's unclear: se o usuário quer commitá-las agora (recomendado — regra "tudo versionado") ou se há mais trabalho planejado nelas.
   - Recommendation: commit na W1 como tarefa explícita; evidência de UAT sempre sobre código versionado.

2. **Casos negativos de permissão sem role seedada (UAT-02 #5: ninguém deixa de ter manutencoes.view)**
   - What we know: todas as 6 roles seedadas têm `manutencoes.view`. O caso negativo do item 5 exige usuário custom.
   - What's unclear: se o executor aceita criar usuário sem roles via tinker (login funciona; sidebar vazia; API 403) ou se prefere criar uma role mínima pela tela admin.
   - Recommendation: criar via tinker usuário SEM roles para o caso negativo do sidebar e documentar o 403 esperado.

3. **Corrigir os 7 erros de typecheck nesta fase?**
   - What we know: não bloqueiam o dev server nem os cenários; bloqueiam `npm run build`.
   - What's unclear: se a evidência exige build de produção (screenshots estáticos via nginx) — hoje impossível sem corrigir os 7 erros OU sem montar dist no nginx.
   - Recommendation: não corrigir (escopo da Fase 19); evidência via dev server. Perguntar ao usuário na discussão se build é critério de aceite.

4. **Re-executar a suíte completa como gate (277s) ou apenas os 3 filtros de apoio?**
   - What we know: 15-VERIFICATION.md (hoje) confirma 165 passed; 2 filtros re-executados verdes na pesquisa.
   - What's unclear: custo/benefício do gate completo na W1 (5 min) vs filtros (~2 min).
   - Recommendation: gate completo na W1 (evidência forte e barata de ambiente são), filtros por onda.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Docker Compose (containers labcontrol) | Backend API, PostgreSQL, Redis | ✓ (4/4 healthy) | postgres 17-alpine, redis 7-alpine, nginx stable, php (Laravel 13.20.0) | — |
| Vite dev server (:5173) | UI para os cenários UAT | ✓ rodando | vite 8.1.5 | Iniciar com `npm run dev` em frontend/ |
| Backend API via nginx (:80) | `/api/*` (proxy do vite) | ✓ | — | — |
| UI via nginx (http://localhost) | SPA em produção | ✗ (500 — dist não montado/stale) | — | **Usar dev server :5173** (único caminho) |
| PostgreSQL real populado | Dados dos cenários | ✗ **vazio** (0 users/roles/equipments) | postgres 17 | `migrate:fresh --seed --force` (W1) — também liquida Manual-Only da Fase 15 |
| PHPUnit (no container php) | Evidência automatizada | ✓ | ^12.5.12 (sqlite :memory:) | — |
| npm / vue-tsc | typecheck opcional | ✓ | vue-tsc 2.2.2 | 7 erros pré-existentes conhecidos |
| frontend/dist (build) | nginx/produção | ✗ stale (27/07/2026), sem mount | — | Não usar nesta fase |

**Missing dependencies with no fallback:**
- Nenhum — todos os bloqueios têm caminho definido (seed na W1; UI via dev server).

**Missing dependencies with fallback:**
- UI em produção (nginx): usar dev server :5173 — verificado funcional (proxy `/api` → :80, cookies Sanctum funcionam cross-port).
- Banco vazio: seed na W1 (banco vazio ⇒ `migrate:fresh` sem risco de perda).
- Build de produção: não é requisito desta fase (decisão de escopo na discussão).

## Validation Architecture

> `workflow.nyquist_validation` = true (config.json — key presente, não false). Seção obrigatória.

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5.12 (Laravel 13.20.0) |
| Config file | `backend/phpunit.xml` (DB sqlite :memory:) |
| Quick run command | `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<teste>` |
| Full suite command | `docker compose -f docker/docker-compose.yml exec -T php php artisan test` (~277s) |
| Frontend check (opcional) | `npm run typecheck` (frontend/) — 7 erros pré-existentes conhecidos |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| UAT-01 | Fluxo de criação de aferição, tolerância within/outside, notificação, histórico por equipamento (apoio aos 6 cenários) | integration | `php artisan test --filter=VerificationUatFixTest` | ✅ `backend/tests/Feature/VerificationUatFixTest.php` (5 tests/28 assertions) |
| UAT-02 | Fluxos de manutenção: criar, completar (com peças), cancelar, histórico por equipamento (apoio aos 5 itens) | integration | `php artisan test --filter=MaintenanceVerificationTest` | ✅ `backend/tests/Feature/MaintenanceVerificationTest.php` (6 tests/23 assertions) |
| UAT-01 #5/#6, UAT-02 #5 | RBAC: usuário sem permissão → 403; admin bypass (apoio aos cenários de permissão) | integration | `php artisan test --filter=RbacRegressionTest` | ✅ `backend/tests/Feature/RbacRegressionTest.php` (14 tests/23 assertions) |
| UAT-01 #1 (BUG-01) | Seed idempotente (admin único, 6 roles, 5+5 categorias) — base para os dados de UAT | integration | `php artisan test --filter=SeederIdempotencyTest` | ✅ `backend/tests/Feature/SeederIdempotencyTest.php` (2 tests/14 assertions) |
| UAT-01/UAT-02 (gate) | Suíte completa verde antes da evidência manual | integration | `php artisan test` (165 passed / 473 assertions) | ✅ (suite) |
| Cenários visuais (todos) | Execução manual na UI (dev server :5173) com registro em 16-UAT.md | manual-only | — (documentado no 16-UAT.md) | ✅ formato estabelecido (09/15-UAT.md) — justificativa: rendering condicional, toasts, layout PrimeVue e paginação/expansão exigem olho humano |

### Sampling Rate
- **Per task commit:** `docker compose -f docker/docker-compose.yml exec -T php php artisan test --filter=<teste-da-onda>` (ou suíte completa na W1).
- **Per wave merge:** suíte completa verde (165 passed).
- **Phase gate:** suíte completa verde + 11 cenários documentados em 16-UAT.md antes do `/gsd-verify-work`.

### Wave 0 Gaps
- [ ] Nenhum — toda a infraestrutura de teste já existe e está verde (VerificationUatFixTest, MaintenanceVerificationTest, RbacRegressionTest, SeederIdempotencyTest). Não há arquivos de teste a criar nesta fase.
- (Opcional) Se a evidência incluir `npm run build`, os 7 erros de typecheck viram Wave 0 — decisão de escopo (Open Question 3).

## Security Domain

> `security_enforcement` não está explicitamente `false` no config.json → seção incluída. Esta fase NÃO escreve código; o domínio de segurança é **verificação de controles existentes**.

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Sanctum SPA (cookies HttpOnly + CSRF), rate limit login check-após-falha (5 falhas → 429 PT, sucesso limpa) — verificado por RateLimitTest; login manual admin@labcontrol.com/@dmin123 |
| V3 Session Management | yes | Sessões Redis, cookies HttpOnly; logout revoga tokens — verificado via AuthController (não alterado nesta fase) |
| V4 Access Control | yes | RBAC custom `CheckPermission` (alias `permission` → middleware) em 14 controllers HasMiddleware; RoleController exige `roles.manage` nas mutações; `GET /roles` aberto p/ leitura — verificado por RbacRegressionTest (403 real). Cenários 5/6 do UAT-01 e item 5 do UAT-02 exercitam o gating visual alinhado ao enforcement |
| V5 Input Validation | yes | Form Requests Laravel (StoreVerificationRequest/StoreMaintenanceOrderRequest etc.) — validação centralizada; não é alvo de mudança nesta fase |
| V6 Cryptography | no | Senhas via bcrypt (Hash::make), não há criptografia custom neste escopo |

### Known Threat Patterns for {stack}

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Bypass de permissão via UI (renderização condicional burlável) | Elevation of Privilege | Gating visual (`v-if hasPermission`) é UX; a barreira de verdade é o middleware `permission:` → 403 (RbacRegressionTest prova). O UAT verifica que o visual reflete o enforcement |
| Escalada via RoleController (mutações de role sem permissão) | Elevation of Privilege | `roles.manage` exigido em store/update/destroy/syncPermissions (Fase 15-01); verificado por teste dedicado |
| Força bruta de login | Denial of Service / Spoofing | Rate limiter check-após-falha com limpeza no sucesso; 429 com mensagem PT |
| Acesso a histórico de equipamento por outro usuário | Information Disclosure | Rotas `byEquipment` sob `permission:afericoes.view`/`manutencoes.view` — 403 sem permissão |

## Sources

### Primary (HIGH confidence — verificados por leitura direta/execução nesta sessão)
- `.planning/phases/09-afericoes/09-UAT.md` (via `git show ddff1a5^:...`) — 6 cenários UAT-01, texto original
- `.planning/phases/10-manutencaoes/10-VERIFICATION.md` e `10-VALIDATION.md` (via `git show ddff1a5^:...`) — 5 itens visuais UAT-02 + Manual-Only
- `.planning/phases/15-corre-es-de-funcionamento/15-01-SUMMARY.md`, `15-02-SUMMARY.md`, `15-VERIFICATION.md`, `15-VALIDATION.md`, `15-UAT.md`, `deferred-items.md` — o que a Fase 15 entregou e o que ficou pendente
- `backend/tests/Feature/VerificationUatFixTest.php` (5 tests/28 assertions — **executado verde hoje**) e `MaintenanceVerificationTest.php` (6 tests/23 assertions — **executado verde hoje**)
- `backend/app/Services/VerificationService.php` (getPendingVerifications corrigido — commit 022c30b), `VerificationResource.php` (is_outside_range)
- Frontend: `VerificationPendingPage.vue`, `VerificationFormDialog.vue`, `VerificationHistoryTab.vue`, `MaintenanceListPage.vue`, `MaintenanceOpenDialog.vue`, `MaintenanceCloseDialog.vue`, `MaintenanceHistoryTab.vue`, `EquipmentDetailPage.vue` (abas 3/6 + dialogs wiringados)
- `backend/database/seeders/RolePermissionSeeder.php` (perfis/permissões), `AdminUserSeeder.php` (credenciais admin)
- Probes de runtime: `docker ps` (4 containers healthy), tinker (banco vazio), HTTP :80 (500) e :5173 (200), `npm run typecheck` (7 erros), `git status` (8 arquivos modificados)
- `REQUIREMENTS.md` (UAT-01/UAT-02), `ROADMAP.md` (Fase 16 + dependência Fase 15), `STATE.md` (Deferred Items: 6+5 itens UAT)

### Secondary (MEDIUM confidence)
- `10-VALIDATION.md` — Manual-Only Verifications de Manutenções (triangulação dos 5 itens)
- `09-VERIFICATION.md` — lista os mesmos 6 itens como "Human Verification Required" (triangulação)

### Tertiary (LOW confidence)
- Nenhum — todas as afirmações desta pesquisa têm fonte primária no repositório ou foram verificadas por execução nesta sessão. Nenhuma descoberta dependente de WebSearch foi usada (fase interna de verificação; sem novo domínio técnico externo a pesquisar — o seam research-plan não foi acionado porque as questões eram 100% internas ao repo).

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — stack já travada e em produção desde v1.0; sem novos pacotes.
- Architecture: HIGH — componentes, rotas e fluxos verificados por leitura direta e execução de testes hoje.
- Pitfalls: HIGH — 8 pitfalls todos observados/verificados nesta sessão (probes reais).
- Onde baixar a guarda: número exato da suíte completa (165) e wording da evidência manual dependem da execução do gate na W1 (Assumption A6).

**Research date:** 2026-08-09
**Valid until:** 2026-08-16 (ambiente mutável — seeds, containers e working tree mudam durante a fase; re-validar W1 probes antes de executar)
