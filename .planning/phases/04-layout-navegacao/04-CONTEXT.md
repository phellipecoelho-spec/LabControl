# Phase 4: Layout e Navegacao - Context

**Gathered:** 2026-07-19
**Status:** Ready for planning

<domain>
## Phase Boundary

App shell completo com sidebar colapsavel, topbar com menu do usuario e notificacoes, tema escuro responsivo com toggle dark/light, e navegacao estruturada por modulos agrupados em categorias.

**Requisitos cobertos:**
- LAYOUT-01: Tema escuro responsivo com design moderno
- LAYOUT-02: Sidebar com navegacao por modulos
- LAYOUT-03: Topbar com notificacoes e menu do usuario

</domain>

<decisions>
## Implementation Decisions

### 1. App Shell Layout
- **D-01:** Layout composto por sidebar (esquerda) + topbar (superior) + area de conteudo principal
- **D-02:** Sidebar colapsavel estilo Linear: expandida mostra icone + rotulo (240px), colapsada mostra apenas icones (64px)
- **D-03:** Mobile: sidebar vira overlay drawer (nao permanente)
- **D-04:** Topbar fixa no topo com altura padrao (~64px)
- **D-05:** Hamburger toggle na topbar para recolher/expandir sidebar

### 2. Topbar Content
- **D-06:** Menu do usuario: avatar, nome, link para perfil (/profile), logout
- **D-07:** Dark/Light mode toggle (sol/lua)
- **D-08:** Notificacoes: icone com badge (placeholder para fase futura)
- **D-09:** Sem breadcrumbs na topbar

### 3. Theme System
- **D-10:** Tema escuro (dark) como padrao
- **D-11:** Toggle manual dark/light na topbar com persistencia via localStorage (classe `.app-dark` no elemento raiz)
- **D-12:** Nao implementar deteccao automatica de preferencia do SO (`prefers-color-scheme`)
- **D-13:** Usar PrimeVue Aura theme com `darkModeSelector: '.app-dark'` (ja configurado em `main.ts`)

### 4. Navegacao por Modulos
- **D-14:** Modulos organizados na sidebar por categorias com cabecalhos de grupo
- **D-15:** Categorias: Gestao (Equipamentos, Estoque), Operacoes (Movimentacoes, Emprestimos, Calibracoes, Afericoes, Manutencoes), Administracao (Usuarios, Logs de Auditoria, Configuracoes), Relatorios
- **D-16:** Dashboard fica fora das categorias, no topo da sidebar (fixo)
- **D-17:** Modulos visiveis conforme permissao do usuario (back-end ja tem `hasPermission()`)

### Agent's Discretion
- Ordem exata dos modulos dentro de cada categoria
- Animacoes e transicoes da sidebar (collapse/expand, mobile drawer)
- Implementacao do toggle dark/light (icone, transicao CSS)
- Altura exata da topbar (sugerido ~64px)
- Estrutura de componentes (AppLayout.vue, AppSidebar.vue, AppTopbar.vue)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — LAYOUT-01, LAYOUT-02, LAYOUT-03
- `.planning/PROJECT.md` — Stack, dark theme inspirado Power BI / Linear / Notion

### Existing Codebase
- `frontend/src/App.vue` — Componente raiz atual (apenas `<router-view />` + `<Toast />`)
- `frontend/src/main.ts` — Bootstrap com PrimeVue Aura theme, darkModeSelector `.app-dark`
- `frontend/src/styles/global.css` — Estilos globais com classe `.app-dark`
- `frontend/src/router/index.ts` — Router com guards de autenticacao e roles
- `frontend/src/router/routes.ts` — Definicoes de rotas (pagina de login nao deve ter layout)
- `frontend/src/stores/auth.ts` — Auth store com `hasRole()`, `hasPermission()`, `user`

### Prior Phase Context
- `.planning/phases/03-usuarios-permissoes/03-CONTEXT.md` — Permissoes por role, admin bypass
- `.planning/phases/02-autenticacao/02-CONTEXT.md` — Sanctum SPA, auth store, router guards

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **PrimeVue Drawer/PanelMenu/Sidebar** — Componentes prontos para sidebar e menus
- **PrimeVue Menubar/Toolbar** — Componentes para topbar
- **Avatar component** — Ja importado e usado em UsersPage
- **Toast component** — Ja configurado globalmente em App.vue
- **`.app-dark` class** — Sistema de dark mode ja configurado no PrimeVue

### Established Patterns
- **Lazy loading de rotas** — `component: () => import(...)` em todas as rotas
- **`<script setup lang="ts">`** — Composition API com TypeScript
- **Pinia stores** — Stores em `stores/` para estado global
- **Modulos** — `frontend/src/modules/{module}/pages/` para paginas

### Integration Points
- `App.vue` — Substituir `<router-view />` plano por layout com sidebar + topbar
- `router/index.ts` — Rotas publicas (login, register) devem usar layout diferente ou nenhum
- `router/routes.ts` — Adicionar meta para associar rotas a modulos (grupo, icone, label)
- `stores/auth.ts` — Usuario autenticado ja disponivel para menu do usuario

### Auth views (exclusoes do layout)
- LoginView, RegisterView, ForgotPasswordView, ResetPasswordView, VerifyEmailView — devem usar layout simples (sem sidebar/topbar), manter estrutura atual com AuthForm

</code_context>

<specifics>
## Specific Ideas

- Inspirado em Linear para sidebar colapsavel e visual clean
- Tema escuro como padrao com toggle manual para light
- Notificacoes como placeholder (icone com badge zero, sem funcionalidade ainda)

</specifics>

<deferred>
## Deferred Ideas

- **Breadcrumbs** — Mencionado mas nao incluido na topbar. Pode ser util em fases futuras com navegacao profunda (ex: modulo de equipamentos com sub-paginas)
- **Atalhos de teclado** — Navegacao por teclado na sidebar pode ser adicionada depois
- **Notificacoes funcionais** — Placeholder nesta fase, implementacao real em fase futura
- **Preferencia do SO (prefers-color-scheme)** — Decidido nao implementar agora, mas pode ser adicionado depois

</deferred>

---

*Phase: 04-Layout e Navegacao*
*Context gathered: 2026-07-19*
