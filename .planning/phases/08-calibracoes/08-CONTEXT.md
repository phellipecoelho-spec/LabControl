# Phase 8: Calibrações - Context

**Gathered:** 2026-07-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Módulo de controle de calibrações periódicas de equipamentos — registro de eventos de calibração com suporte a partes/componentes, anexo de certificados, alerta de vencimento com 30 dias de antecedência, e consulta de histórico por equipamento via listagem filtrada.

**Calibração** (esta fase): evento programado com periodicidade, gera certificado, realizada por laboratório externo ou interno.
**Aferição** (Phase 9): verificação diária/semanal pelo operador, sem certificado, apenas registro.

**Requisitos cobertos:**
- CAL-01: Usuário pode gerenciar agenda de calibrações periódicas
- CAL-02: Usuário pode anexar certificados de calibração
- CAL-03: Sistema alerta quando calibração está vencida
- CAL-04: Usuário pode consultar histórico de calibrações por equipamento

</domain>

<decisions>
## Implementation Decisions

### 1. Modelo de Dados

- **D-01:** Calibração pertence a UM equipamento (FK `equipment_id`). Relacionamento 1:N (Equipment → Calibrations)
- **D-02:** Modelagem de periodicidade **simples** — cada calibração registra `interval_value` (ex: 6) e `interval_unit` (months, days, hours). A próxima data é calculada no momento da conclusão: `next_due_at = completed_at + interval`. Sem tabela de schedules
- **D-03:** Status da calibração: `scheduled` (agendada/pendente), `completed` (concluída), `cancelled` (cancelada). Um equipamento com calibração vencida = última `completed` com `next_due_at < now()`
- **D-04:** Campos da tabela `calibrations`: equipment_id, status, scheduled_date, completed_at (nullable), next_due_at (nullable), interval_value (int), interval_unit (string: months/days/hours), responsible (responsável pela calibração), laboratory (laboratório externo ou interno), certificate_number (nullable), notes, created_by (FK users)
- **D-05:** Suporte a partes/componentes — campo opcional `part_name` na calibração para especificar qual parte do equipamento foi calibrada (ex: "sensor de temperatura", "braço robótico"). Sem tabela separada de partes nesta fase
- **D-06:** Diferenciação Calibração vs Aferição: Calibração tem periodicidade + certificado + laboratório + custo associado. Aferição (Phase 9) é verificação operacional sem certificado

### 2. Certificados (CAL-02)

- **D-07:** Modelagem 1:N direta — tabela `calibration_certificates` com FK `calibration_id`, mesmos padrão de `equipment_photos`
- **D-08:** Campos da tabela `calibration_certificates`: calibration_id, filename (original), filepath (storage), mime_type, size_bytes, certificate_number (número do certificado), issuer (emissor), issued_at (data de emissão), validity_start, validity_end, notes
- **D-09:** Armazenamento em `storage/app/public/calibrations/certificates/`
- **D-10:** Upload via service similar a EquipmentPhotoService, com validação de tipo (PDF, imagens)

### 3. Alerta de Vencimento (CAL-03)

- **D-11:** Alerta único com **30 dias de lead time** — comando scheduled diário que verifica `next_due_at` entre `now()` e `now() + 30 days` para calibrações `completed`, criando notificações in-app para administradores e supervisores
- **D-12:** Mesmo padrão do comando `CheckOverdueLoans` (Phase 7) — Notification class + scheduled command
- **D-13:** Sem notificação por email nesta fase

### 4. Histórico (CAL-04)

- **D-14:** Histórico por equipamento via **página de listagem com filtro** — não como aba no DetailPage do equipamento
- **D-15:** ListPage de calibrações com DataTable e filtros: por equipamento (select), período (date range), status, laboratório
- **D-16:** Colunas da lista: Equipamento, Parte (se houver), Data Agendada, Data Conclusão, Próxima Data, Laboratório, Status, Ações

### 5. Interface

- **D-17:** Padrão ListPage + criação por Dialog modal (mesmo padrão Empréstimos)
- **D-18:** DetailPage com abas: Dados da Calibração (campos + equipamento), Certificados (lista de certificados com download/upload), Timeline (histórico de status via LogsActivity)
- **D-19:** Dialog de criação: selecionar equipamento (select), parte (texto opcional), data agendada, intervalo (valor + unidade), responsável, laboratório, observações
- **D-20:** Ao concluir calibração (status → completed): preencher `completed_at`, `next_due_at` (calculado), número certificado. Dialog separado de conclusão

### 6. Permissões e Navegação

- **D-21:** Permissões a seedar: `calibracoes.view`, `calibracoes.create`, `calibracoes.edit`, `calibracoes.concluir`, `calibracoes.cancel`
- **D-22:** Sidebar: categoria "Operações" → "Calibrações" (ícone pi-verified, permissão calibracoes.view)
- **D-23:** Rotas: `/calibrations` (index), `/calibrations/{id}` (show)

### Agent's Discretion
- Nomes específicos de rotas, controllers, services seguindo convenções dos módulos existentes
- Ordem de implementação (backend DB → backend CRUD → frontend CRUD + notificação)
- Índices do banco além dos obrigatórios (FKs)
- Layout exato de cada aba da DetailPage (campos, ordem, grid)
- Estratégia de validação (campos obrigatórios, transições de status)
- Template da notificação in-app (texto, prioridade, link)
- Ícone e label exatos para os botões de ação
- Quantidade/limite de certificados por calibração
- Formato de exibição da periodicidade na UI (ex: "6 meses", "30 dias", "1000 horas")
- Cálculo de `next_due_at` considerando dias úteis ou corridos

</decisions>

<deferred>
## Deferred Ideas
- Notificação por email de vencimento — depende da infraestrutura de email
- Calendário visual mensal de calibrações — fase futura de UI
- Gatilho por horas de uso (ex: calibrar a cada 1000 horas de operação) — requer integração com controle de uso do equipamento
- Workflow de aprovação de certificados — fase futura
- Relatórios de calibrações (equipamentos mais calibrados, laboratórios mais utilizados, etc.) — Phase 12 (Relatórios)
- Tabela parametrizada de partes/componentes por equipamento — fase futura se houver demanda
- Integração com laboratórios externos via API — fase futura

</deferred>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — CAL-01, CAL-02, CAL-03, CAL-04
- `.planning/PROJECT.md` — Stack, key decisions, UUIDs, Sanctum

### Prior Phase Context
- `.planning/phases/05-equipamentos/05-CONTEXT.md` — Equipment CRUD pattern, photo upload service (base para certificates)
- `.planning/phases/06-estoque/06-CONTEXT.md` — Inventory movement pattern (referência para registros imutáveis)
- `.planning/phases/07-emprestimos/07-CONTEXT.md` — Overdue notification command pattern, ListPage+Dialog+DetailPage layout, permissions, sidebar navigation

### Codebase Maps
- `.planning/codebase/ARCHITECTURE.md` — Layers, data flow, controller/service pattern
- `.planning/codebase/CONVENTIONS.md` — Naming, imports, backend/frontend conventions
- `.planning/codebase/STACK.md` — Technology versions, package decisions

### Navigation & Routes
- `frontend/src/router/routes.ts` — Existing route patterns
- `frontend/src/types/navigation.ts` — Sidebar structure
- `backend/database/seeders/RolePermissionSeeder.php` — Permission seeding pattern

</canonical_refs>

<folded_todos>
## Folded Todos
Nenhum — nenhum TODO pendente correspondeu à Phase 8.

</folded_todos>
