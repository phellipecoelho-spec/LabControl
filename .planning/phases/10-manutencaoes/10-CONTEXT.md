# Phase 10: Manutenções - Context

**Gathered:** 2026-07-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Módulo de ordens de manutenção preventiva e corretiva de equipamentos — abertura de ordens, designação de técnico, execução com registro de parecer técnico e peças utilizadas, fechamento com custo e horas, histórico como aba no DetailPage do equipamento, e página de listagem com filtros para gestão centralizada.

**Manutenção Preventiva:** intervalo-based (like Calibrações). Registra `interval_value` + `interval_unit`. Ao concluir, próxima data é calculada automaticamente.
**Manutenção Corretiva:** sob demanda, sem intervalo, sem agendamento recorrente.

**Requisitos cobertos:**
- MAINT-01: Usuário pode abrir ordens de manutenção
- MAINT-02: Sistema mantém histórico de manutenções preventivas e corretivas

</domain>

<decisions>
## Implementation Decisions

### 1. Modelo de Dados — Ordens e Partes

- **D-01:** Tipo único via campo `type` (enum: `preventive`, `corrective`). Mesmo modelo e workflow para ambos os tipos. Sem tabelas separadas
- **D-02:** Workflow de status: `open` → `in_progress` → `completed` | `cancelled`. Transições simples que cobrem ambos os tipos. Ao criar: status `open`. Ao designar técnico ou iniciar execução: `in_progress`. Ao concluir: `completed`. Ao cancelar: `cancelled`
- **D-03:** Campos da tabela `maintenance_orders`: id (UUID), equipment_id (FK equipments), type, status, priority (low/medium/high/critical), description (texto), scheduled_date (datetime nullable), assigned_to (FK users, nullable), opened_by (FK users), completed_at (datetime nullable), resolution (text nullable — parecer técnico), time_spent (decimal nullable — horas), cost (decimal nullable), interval_value (int nullable), interval_unit (string nullable: days/months/hours), next_due_at (datetime nullable), notes (text nullable), created_by, updated_by, deleted_by, timestamps + softDeletes
- **D-04:** Agendamento preventivo **interval-based** — cada ordem preventiva registra `interval_value` + `interval_unit`. Ao concluir, `next_due_at` é calculado automaticamente: `completed_at + interval`. Sem tabela de schedules separada
- **D-05:** Peças/insumos utilizados — tabela pivot `maintenance_order_parts` com FK `maintenance_order_id` + FK `inventory_item_id` (Phase 6 — Estoque), `quantity` (decimal), `unit_cost` (decimal nullable), `created_by`, timestamps

### 2. Abertura da Ordem (MAINT-01)

- **D-06:** Formulário de abertura contém: equipment_id (select), type (preventive/corrective), priority (select low/medium/high/critical), description (textarea), scheduled_date (datepicker, opcional). `opened_by` = auth()->id(). Status inicial = `open`
- **D-07:** `assigned_to` (técnico responsável) **não** é preenchido na abertura — é designado depois via edição da ordem
- **D-08:** Ao criar ordem, disparar notificação in-app para supervisores (permissão `manutencoes.edit`) informando que nova ordem foi aberta

### 3. Execução e Fechamento

- **D-09:** Fechamento (status → `completed`): formulário com resolution (textarea, parecer técnico), time_spent (input number, horas), cost (input number, R$), peças utilizadas (multi-select de inventory_items + quantidade). `completed_at` = now() com opção de ajuste manual
- **D-10:** Ao concluir ordem preventiva: calcular `next_due_at = completed_at + interval`. Se `next_due_at` calculado, criar automaticamente uma nova ordem preventiva com `scheduled_date = next_due_at`
- **D-11:** Cancelamento (status → `cancelled`): campo `notes` opcional com motivo do cancelamento

### 4. Histórico e Timeline (MAINT-02)

- **D-12:** Histórico exibido como **aba no EquipmentDetailPage** (same as Aferições, Phase 9). Timeline com: data de abertura, tipo, status, prioridade, técnico responsável, data de conclusão
- **D-13:** Adicionalmente, **página de listagem dedicada** `/maintenance` com DataTable e filtros: equipment (select), type, status, priority, date range. Para gestão centralizada de todas as ordens

### 5. Permissões e Navegação

- **D-14:** Permissões: `manutencoes.view`, `manutencoes.create`, `manutencoes.edit`, `manutencoes.concluir`
- **D-15:** Sidebar já scaffolded: categoria "Operações" → "Manutenções" (ícone `pi pi-wrench`, permissão `manutencoes.view`). Rota existente: `maintenance.index`. Rota para criação: `maintenance.create`. Rotas adicionais conforme necessidade
- **D-16:** Aba "Manutenções" no EquipmentDetailPage visível apenas com permissão `manutencoes.view`
- **D-17:** Botão "Abrir Ordem" / "Concluir" gated por `manutencoes.create` e `manutencoes.concluir` respectivamente

### 6. Notificações

- **D-18:** Notificação in-app (`database` channel) quando ordem é criada: notificar todos usuários com permissão `manutencoes.edit` (supervisores). Classe: `App\Notifications\MaintenanceOrderCreated`
- **D-19:** Sem comando scheduled para alertas de manutenção preventiva nesta fase — o cálculo de `next_due_at` é feito no momento da conclusão, e a nova ordem já é criada automaticamente (D-10). O alerta de vencimento pode ser adicionado em fase futura

### the agent's Discretion
- Nomes específicos de controllers, services, stores (seguindo convenções dos módulos existentes)
- Ordem de implementação (backend DB → backend CRUD → frontend)
- Layout exato do formulário de abertura (campos, ordem, grid)
- Layout exato do formulário de fechamento
- Template da notificação in-app (texto, prioridade, link)
- Ícones específicos para botões/ações (além do pi-wrench definido)
- Se a criação automática de ordem preventiva (D-10) é feita via evento/observer ou no service

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — MAINT-01, MAINT-02
- `.planning/PROJECT.md` — Stack, key decisions, UUIDs, Sanctum

### Prior Phase Context
- `.planning/phases/08-calibracoes/08-CONTEXT.md` — Interval-based scheduling pattern, permission model (view/create/edit/concluir/cancel), scheduled command pattern
- `.planning/phases/09-afericoes/09-CONTEXT.md` — History tab in EquipmentDetailPage pattern, dynamic form pattern, permission gating on tabs
- `.planning/phases/05-equipamentos/05-CONTEXT.md` — Equipment model structure, DetailPage tabs
- `.planning/phases/06-estoque/06-CONTEXT.md` — Inventory items model (para pivot de peças)

### Codebase Maps
- `.planning/codebase/ARCHITECTURE.md` — Layers, data flow, controller/service pattern
- `.planning/codebase/CONVENTIONS.md` — Naming, imports, backend/frontend conventions
- `.planning/codebase/STACK.md` — Technology versions, package decisions

### Navigation & Routes
- `frontend/src/types/navigation.ts` — Sidebar structure (Manutenções já scaffolded: `pi pi-wrench`, rota `maintenance.index`, permissão `manutencoes.view`)
- `frontend/src/router/routes.ts` — Existing route patterns (rota `maintenance.index` já registrada como placeholder)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **CalibrationService** — Padrão de service layer com DB::transaction para criação de registros compostos (reutilizável para criação de ordem + partes)
- **ToleranceExceeded / CalibrationDue** — Padrão de notificação in-app (database channel) — reutilizável para MaintenanceOrderCreated
- **VerificationHistoryTab** — Padrão de aba de histórico no EquipmentDetailPage com DataTable paginada, expansão de linhas, color-coded tags (reutilizável para aba de manutenções)
- **LoanCreateDialog / CalibrationCreateDialog** — Padrão de Dialog modal para criação de registros com seleção de equipamento
- **InventoryMovementDialog** — Padrão de Dialog com seleção de inventory_item + quantidade (reutilizável para peças no fechamento)
- **CheckCalibrationDue / CheckOverdueLoans** — Padrão de comando scheduled (não usado nesta fase, mas disponível para futuro alerta de preventiva vencida)

### Established Patterns
- **CRUD module:** Migration compound → Models → Services → Controllers → Frontend types/service/store → UI pages
- **Permissions:** RolePermissionSeeder com middleware estático nos controllers
- **Notifications:** App\Notifications\* com criação via Notification::send()
- **Sidebar:** Categoria "Operações" com sub-módulos (Manutenções já scaffolded)
- **DetailPage tabs:** EquipmentDetailPage com tabs sequenciais (Principal=0, Localização=1, Técnica=2, Aferições=3, Arquivos=4, Logs=5) — inserir Manutenções em posição apropriada

### Integration Points
- **EquipmentDetailPage** — Adicionar nova aba "Manutenções" (TabPanel) + VerificationFormDialog-style dialog para abrir ordem
- **Equipment Model** — Adicionar relacionamento hasMany com MaintenanceOrder
- **InventoryItem Model** — Adicionar relacionamento belongsToMany via maintenance_order_parts
- **InventoryItem** — Select de peças/insumos para o formulário de fechamento (consultar inventory_items com estoque disponível)
- **Sidebar** — Já scaffolded, atualizar rota de PlaceholderPage para MaintenanceListPage

</code_context>

<specifics>
## Specific Ideas

- Interface de abertura deve ser rápida — operador reporta problema em poucos cliques
- Preventiva: ao concluir, criar automaticamente a próxima ordem (D-10) para manter a recorrência sem agendador externo
- Peças utilizadas devem descontar do estoque no momento do fechamento (movimentação de saída)
- Timeline colorida: verde para concluída, amarelo para em andamento, vermelho para atrasada, cinza para cancelada

</specifics>

<deferred>
## Deferred Ideas
- Alerta de manutenção preventiva vencida (scheduled command similar a CheckCalibrationDue) — pode ser adicionado em fase futura se necessário
- Checklist de itens de verificação na manutenção preventiva — fase futura
- Anexar fotos/laudos à ordem de manutenção — pode ser adicionado como extensão do módulo
- Workflow de aprovação de ordens (supervisor aprova antes de iniciar) — fase futura
- Integração com dashboard (manutenções atrasadas, custo por equipamento) — Phase 11 (Dashboard) ou Phase 12 (Relatórios)

</deferred>

---

*Phase: 10-manutencaoes*
*Context gathered: 2026-07-25*
