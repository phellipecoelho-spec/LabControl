# Phase 11: Dashboard - Context

**Gathered:** 2026-07-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Módulo de dashboard central com indicadores visuais (ECharts) consolidando dados de todos os módulos do sistema — equipamentos, calibrações, movimentações de estoque, empréstimos, aferições e manutenções. Servindo como página inicial (rota `/`) com visão geral do laboratório.

**Requisitos cobertos:**
- DASH-01: Dashboard com indicadores (ECharts)
- DASH-02: Gráficos de equipamentos, calibrações, movimentações

</domain>

<decisions>
## Implementation Decisions

### 1. Layout do Dashboard
- **D-01:** Widget Grid responsivo com 3 colunas flex (reorganiza para 2 em tablet, 1 em mobile)
- **D-02:** Fileira horizontal de 5 KPIs numéricos no topo, antes dos gráficos
- **D-03:** Gráficos ocupam largura total ou 2 colunas do grid conforme necessidade
- **D-04:** Estado vazio: mensagem central com onboarding e links para páginas de cadastro

### 2. KPIs (Linha do Topo)
- **D-05:** 5 KPIs principais: Total Equipamentos, Calibrações a Vencer (próximos 30d), Empréstimos Ativos, Aferições Pendentes (hoje), Manutenções Abertas
- **D-06:** Cada KPI é clicável e navega para a listagem do módulo correspondente com filtro aplicado
- **D-07:** KPIs carregam do endpoint único de dashboard (não de chamadas separadas)

### 3. Gráficos (ECharts)
- **D-08:** 3 gráficos principais:
  1. Equipamentos por Categoria — gráfico de pizza/rosca
  2. Calibrações nos Próximos 6 Meses — gráfico de barras/barras empilhadas
  3. Movimentações de Estoque por Mês — gráfico de linha/área
- **D-09:** Período padrão dos gráficos: últimos 12 meses
- **D-10:** Gráficos com drill-down: clicar em elemento navega para listagem filtrada (router push com query params)

### 4. API do Dashboard
- **D-11:** Endpoint único: `GET /api/v1/dashboard`
- **D-12:** Resposta em estrutura nomeada por seção: `{ kpis: {...}, charts: { equipments_by_category: [...], calibrations_timeline: [...], stock_movements: [...] } }`
- **D-13:** Cache no backend com Redis, TTL de 5 minutos
- **D-14:** Lógica de agregação em `DashboardService` (`backend/app/Services/DashboardService.php`)

### 5. Interatividade
- **D-15:** Atualização manual apenas (botão "Atualizar" no topo), sem polling automático
- **D-16:** Loading state: spinner centralizado enquanto carrega

### 6. Filtros
- **D-17:** Seletor de período global no topo do dashboard (DateRangePicker do PrimeVue)
- **D-18:** Nenhum filtro adicional (laboratório, categoria) — apenas período

### 7. Estrutura Frontend
- **D-19:** Segue o padrão de módulo: `frontend/src/modules/dashboard/{pages,components,services,store,types,routes}/`
- **D-20:** DashboardService no frontend: chamada Axios para `/api/v1/dashboard`
- **D-21:** DashboardStore (Pinia) para estado do dashboard
- **D-22:** vue-echarts para renderização dos gráficos (já instalado)

### the agent's Discretion
- Tipos de gráfico específicos (pizza vs rosca, barra simples vs empilhada, linha vs área) — definir durante planejamento baseado nos dados disponíveis
- Cores e tema dos gráficos — seguir o tema escuro Aura do PrimeVue

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Dashboard Module
- `frontend/src/modules/dashboard/pages/DashboardPage.vue` — Placeholder atual (será substituído)
- `frontend/src/modules/dashboard/` — Scaffold do módulo (components/, pages/, routes/, services/, store/, types/)

### Stack
- `frontend/src/main.ts` §30-40 — Configuração PrimeVue + tema escuro Aura
- `frontend/src/router/index.ts` §8-12 — Rota raiz `/` aponta para DashboardPage (lazy-loaded)

### Layout Patterns (Phase 4)
- `frontend/src/modules/layout/` — Componentes de layout (sidebar, topbar, app shell)

### Module Pattern
- `frontend/src/modules/equipment/` — Exemplo de módulo completo (CRUD, store, service, types)
- `.planning/codebase/CONVENTIONS.md` §282-298 — Estrutura de módulo frontend

### Codebase Maps
- `.planning/codebase/STACK.md` — Stack: ECharts 6 + vue-echarts 8 + PrimeVue 5 + Pinia 4
- `.planning/codebase/ARCHITECTURE.md` — Arquitetura geral (SPA + REST API)
- `.planning/codebase/CONVENTIONS.md` — Convenções de código

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **vue-echarts + echarts 6:** Já instalados e prontos para uso (`frontend/package.json`)
- **PrimeVue Card:** Componente Card disponível para widgets de KPI
- **PrimeVue DatePicker:** Para filtro de período global
- **PrimeVue Skeleton:** Disponível para loading states (não usaremos — optou-se por spinner)
- **Pinia:** Stores disponíveis para caching de dados do dashboard

### Established Patterns
- **Módulo frontend:** `modules/{nome}/{pages,components,services,store,types,routes}/` — dashboard segue o mesmo padrão
- **Lazy loading:** Rotas carregadas via `() => import()` — já configurado para dashboard
- **API Services:** Serviços Axios por módulo — `services/dashboardService.ts`
- **Pinia stores:** Stores por módulo — `store/dashboardStore.ts`

### Integration Points
- **Rota `/`:** Já existe e aponta para `DashboardPage.vue` — substituir placeholder
- **Sidebar:** Dashboard já está na sidebar como primeiro item (categoria principal)
- **Backend:** Criar `backend/app/Http/Controllers/Api/V1/DashboardController.php`
- **Backend:** Criar `backend/app/Services/DashboardService.php`
- **Backend:** Criar rota `GET /api/v1/dashboard` em `backend/routes/api.php`
- **Dados existentes:** Equipment, Calibration, InventoryMovement, Loan, Verification, MaintenanceOrder models e controllers (Phases 5-10)

</code_context>

<specifics>
## Specific Ideas

- Inspiração visual: Power BI / Linear / Notion (tema escuro moderno, já adotado no projeto)
- Dashboard como "hub central" — o usuário chega aqui e vê o estado atual do laboratório

</specifics>

<deferred>
## Deferred Ideas

- **Filtro por laboratório/local** — será relevante quando multiempresa/multilaboratório for implementado (fase futura)
- **Dashboard customizável pelo usuário** (reordenar widgets, escolher KPIs) — feature para versão futura
- **Exportar dashboard como PDF/imagem** — melhor colocado na Phase 12 (Relatórios)

</deferred>

---

*Phase: 11-Dashboard*
*Context gathered: 2026-07-27*
