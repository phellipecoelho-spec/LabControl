# Roadmap: LabControl

## Milestones

- ✅ **v0.1 Foundation** — Phases 1-4 (shipped 2026-07-19)
- ✅ **v0.2 Core Business** — Phases 5-8 (shipped 2026-07-25)
- ✅ **v0.3 Advanced Features** — Phases 9-12 (shipped 2026-07-27)
- ✅ **v1.0 Production** — Phases 13-14 (shipped 2026-07-28)
- 🚧 **v1.1 Verificação, Revisão e Ajustes de Layout** — Phases 15-20 (in progress)

## Phases

<details>
<summary>✅ v0.1 Foundation (Phases 1-4) — SHIPPED 2026-07-19</summary>

- [x] Phase 1: Infraestrutura (3 plans) — Docker + Laravel bootstrap + migrations/seeders + setup scripts
- [x] Phase 2: Autenticação (4 plans) — Sanctum SPA auth, frontend views, email verification, password reset
- [x] Phase 3: Usuários e Permissões (4 plans) — 6 roles, 31 permissions, user/role CRUD, profile, activity logging
- [x] Phase 4: Layout e Navegação (3 plans) — Sidebar, topbar, dark mode, responsive, accessibility

</details>

<details>
<summary>✅ v0.2 Core Business (Phases 5-8) — SHIPPED 2026-07-25</summary>

- [x] Phase 5: Equipamentos (6 plans) — 5-table migration, CRUD API + frontend, photo upload, equipment history
- [x] Phase 6: Estoque (3 plans) — Inventory CRUD, movements ledger, three-layer negative stock defense
- [x] Phase 7: Empréstimos (4 plans) — Loan CRUD, status workflow, overdue notifications, partial returns
- [x] Phase 8: Calibrações (4 plans) — Calibration CRUD, certificates, scheduled due-alerts, timeline

</details>

<details>
<summary>✅ v0.3 Advanced Features (Phases 9-12) — SHIPPED 2026-07-27</summary>

- [x] Phase 9: Aferições (2 plans) — Tolerance checking, pending list, form with dynamic params, history tab
- [x] Phase 10: Manutenções (2 plans) — Maintenance orders, preventive auto-creation, parts tracking, history tab
- [x] Phase 11: Dashboard (2 plans) — KPI cards, 3 ECharts charts, Redis cache
- [x] Phase 12: Relatórios (2 plans) — PDF/Excel generation, 4 report types, frontend filter+download

</details>

<details>
<summary>✅ v1.0 Production (Phases 13-14) — SHIPPED 2026-07-28</summary>

- [x] Phase 13: PWA e Offline (2 plans) — Service Worker, IndexedDB cache, sync engine with conflict detection
- [x] Phase 14: Auditoria e Ajustes Finais (5 plans) — Audit coverage tests, UI polish, bug fixes, deploy prep, docs

</details>

### 🚧 v1.1 Verificação, Revisão e Ajustes de Layout (In Progress)

**Milestone Goal:** Garantir funcionamento adequado da aplicação aplicando todas as verificações/revisões pendentes de v1.0 e revisar/ajustar o layout da interface.

- [ ] **Phase 15: Correções de Funcionamento** - BUG-01, BUG-02 — Seeders funcionais em ambiente limpo + bugs da varredura corrigidos
- [ ] **Phase 16: Verificação UAT** - UAT-01, UAT-02 — 6 cenários UAT de Aferições + 5 itens visuais de Manutenções verificados
- [ ] **Phase 17: Autenticação UX** - AUTH-05, AUTH-06 — ForgotPasswordSentView dedicada + feedback claro de verificação de email
- [ ] **Phase 18: Testes E2E** - E2E-01, E2E-02, E2E-03 — Playwright cobrindo autenticação e fluxos críticos de negócio
- [ ] **Phase 19: Revisão de Layout** - LAYOUT-01, LAYOUT-02 — Consistência de tema escuro + responsividade em todos os breakpoints
- [ ] **Phase 20: PWA e Estoque** - PWA-03, INVT-03 — Indicadores offline/sync na UI + alertas de estoque mínimo

## Phase Details

### Phase 15: Correções de Funcionamento
**Goal**: Ambiente limpo funcional — seeders criam admin/roles/permissões e bugs da varredura de verificação são corrigidos
**Depends on**: Nothing (primeira fase do v1.1, sobre a base v1.0 já entregue)
**Requirements**: BUG-01, BUG-02
**Success Criteria** (what must be TRUE):
  1. Após setup em ambiente limpo, o usuário admin consegue logar com as credenciais do seeder (BUG-01)
  2. Roles e permissões criados pelos seeders aparecem aplicados — admin vê os módulos conforme perfil (BUG-01)
  3. Todos os bugs encontrados na varredura de verificação estão corrigidos e os fluxos afetados funcionam sem erros (BUG-02)
  4. Correções validadas sem regressões nos fluxos relacionados (BUG-02)
**Plans**: TBD

### Phase 16: Verificação UAT
**Goal**: Cenários UAT pendentes de Aferições (Fase 09) e Manutenções (Fase 10) executados sem falhas
**Depends on**: Phase 15
**Requirements**: UAT-01, UAT-02
**Success Criteria** (what must be TRUE):
  1. Usuário executa os 6 cenários UAT de Aferições sem falhas ou bloqueios (UAT-01)
  2. Usuário executa os 5 itens visuais UAT de Manutenções sem falhas ou bloqueios (UAT-02)
  3. Resultado de cada cenário documentado (aprovado/reprovado) com evidência (UAT-01, UAT-02)
**Plans**: TBD

### Phase 17: Autenticação UX
**Goal**: Feedback claro nos fluxos de recuperação de senha e verificação de email
**Depends on**: Phase 16
**Requirements**: AUTH-05, AUTH-06
**Success Criteria** (what must be TRUE):
  1. Usuário vê tela dedicada de confirmação (ForgotPasswordSentView) após solicitar recuperação de senha (AUTH-05)
  2. Usuário recebe feedback de sucesso ao verificar email com link válido (AUTH-06)
  3. Usuário recebe feedback claro de erro ao usar link de verificação inválido/expirado (AUTH-06)
  4. As telas de feedback seguem o tema escuro e funcionam em mobile (AUTH-05)
**Plans**: TBD
**UI hint**: yes

### Phase 18: Testes E2E
**Goal**: Suíte E2E com Playwright cobrindo autenticação e fluxos críticos de negócio, executável na base instalada
**Depends on**: Phase 17
**Requirements**: E2E-01, E2E-02, E2E-03
**Success Criteria** (what must be TRUE):
  1. Projeto Playwright configurado na base instalada com script de execução documentado e funcional (E2E-01)
  2. Fluxos de autenticação (login, logout, recuperação de senha) cobertos por testes E2E que passam (E2E-02)
  3. Fluxos críticos (equipamentos, estoque, empréstimos) cobertos por testes E2E que passam (E2E-03)
  4. Suíte completa executa com um único comando documentado (E2E-01)
**Plans**: TBD

### Phase 19: Revisão de Layout
**Goal**: Interface com tema escuro consistente em todas as telas e responsiva nos breakpoints desktop, tablet e mobile
**Depends on**: Phase 18
**Requirements**: LAYOUT-01, LAYOUT-02
**Success Criteria** (what must be TRUE):
  1. Usuário vê tema escuro consistente em todas as telas, sem telas com cores/contraste quebrados (LAYOUT-01)
  2. Usuário utiliza os principais fluxos em desktop, tablet e mobile sem quebra de layout (LAYOUT-02)
  3. Em mobile não há scroll horizontal nem sobreposição de elementos (LAYOUT-02)
  4. Checklist de revisão de layout documentado com status por tela (LAYOUT-01)
**Plans**: TBD
**UI hint**: yes

### Phase 20: PWA e Estoque
**Goal**: Indicadores visuais de offline/sincronização na UI e alertas de estoque mínimo
**Depends on**: Phase 19
**Requirements**: PWA-03, INVT-03
**Success Criteria** (what must be TRUE):
  1. Usuário vê banner de offline quando a conexão cai (PWA-03)
  2. Usuário vê chip de status de sincronização (sincronizando/sincronizado/pendente/erro) na UI (PWA-03)
  3. Usuário recebe alerta de estoque mínimo quando item fica abaixo do limite (INVT-03)
  4. Itens críticos são sinalizados visualmente na listagem/detalhe do estoque (INVT-03)
**Plans**: TBD
**UI hint**: yes

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|---------------|--------|-----------|
| 1. Infraestrutura | v0.1 | 3/3 | Complete | 2026-07-19 |
| 2. Autenticação | v0.1 | 4/4 | Complete | 2026-07-19 |
| 3. Usuários e Permissões | v0.1 | 4/4 | Complete | 2026-07-19 |
| 4. Layout e Navegação | v0.1 | 3/3 | Complete | 2026-07-19 |
| 5. Equipamentos | v0.2 | 6/6 | Complete | 2026-07-20 |
| 6. Estoque | v0.2 | 3/3 | Complete | 2026-07-20 |
| 7. Empréstimos | v0.2 | 4/4 | Complete | 2026-07-25 |
| 8. Calibrações | v0.2 | 4/4 | Complete | 2026-07-25 |
| 9. Aferições | v0.3 | 2/2 | Complete | 2026-07-25 |
| 10. Manutenções | v0.3 | 2/2 | Complete | 2026-07-25 |
| 11. Dashboard | v0.3 | 2/2 | Complete | 2026-07-27 |
| 12. Relatórios | v0.3 | 2/2 | Complete | 2026-07-27 |
| 13. PWA e Offline | v1.0 | 2/2 | Complete | 2026-07-28 |
| 14. Auditoria e Ajustes Finais | v1.0 | 5/5 | Complete | 2026-07-28 |
| 15. Correções de Funcionamento | v1.1 | 0/0 | Not started | - |
| 16. Verificação UAT | v1.1 | 0/0 | Not started | - |
| 17. Autenticação UX | v1.1 | 0/0 | Not started | - |
| 18. Testes E2E | v1.1 | 0/0 | Not started | - |
| 19. Revisão de Layout | v1.1 | 0/0 | Not started | - |
| 20. PWA e Estoque | v1.1 | 0/0 | Not started | - |
