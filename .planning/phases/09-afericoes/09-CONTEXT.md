# Phase 09: Aferições - Context

**Gathered:** 2026-07-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Módulo de verificação operacional diária/semanal de equipamentos — registro de aferições pelo operador, com suporte a múltiplos parâmetros por equipamento, tolerâncias predefinidas por categoria, alerta imediato quando fora da tolerância, e histórico exibido como aba no DetailPage do equipamento.

**Aferição** (esta fase): verificação operacional pelo operador, sem certificado, sem custo, sem laboratório externo. Apenas registro de parâmetros e comparação contra limites de tolerância.
**Calibração** (Phase 8): evento programado com periodicidade, gera certificado, realizada por laboratório externo ou interno.

**Requisitos cobertos:**
- VERF-01: Usuário pode registrar aferições diárias
- VERF-02: Sistema alerta quando limites de tolerância são excedidos

</domain>

<decisions>
## Implementation Decisions

### 1. Modelo de Dados — Parâmetros e Templates

- **D-01:** Modelagem com **templates + parâmetros** — tabela `verification_templates` vinculada à **categoria do equipamento** (FK `equipment_category_id`), contendo a lista predefinida de parâmetros que devem ser aferidos. Tabela `verification_params` (1:N com verifications) para os valores registrados em cada aferição
- **D-02:** Campos da tabela `verification_templates`: id, equipment_category_id, parameter_name, parameter_unit (opcional), tolerance_min (nullable), tolerance_max (nullable), sort_order
- **D-03:** Campos da tabela `verifications`: id, equipment_id, verified_at, operator_id (FK users), notes
- **D-04:** Campos da tabela `verification_params`: id, verification_id, template_id (FK verification_templates), value (numeric), result (`within_range` | `outside_range` | `not_measured`), notes
- **D-05:** Tolerâncias armazenadas **no template** — cada parâmetro já nasce com tolerance_min e tolerance_max. Ao registrar a aferição, o sistema compara o valor lido contra os limites do template e calcula o result automaticamente

### 2. Frequência e Pendências

- **D-06:** Cada equipamento tem campo `verification_frequency` (enum: `daily`, `weekly`, `shift`). O sistema calcula equipamentos pendentes baseado na última aferição + frequência
- **D-07:** Frequência definida **por equipamento** (não por categoria), permitindo que equipamentos da mesma categoria tenham frequências diferentes

### 3. Fluxo de Registro (VERF-01)

- **D-08:** Página principal "Aferições Pendentes" — lista equipamentos que precisam de aferição hoje, calculada por `last_verification_at + frequency < now()`
- **D-09:** Operador clica no equipamento pendente → abre formulário com parâmetros do template pré-carregados → preenche valores → salva
- **D-10:** Ao salvar: criar registro em `verifications` + N registros em `verification_params` com result calculado automaticamente

### 4. Alerta de Tolerância (VERF-02)

- **D-11:** Alerta **imediato** (não batch) — no momento do registro, se algum `verification_param.result` for `outside_range`, exibir alerta visual no formulário e disparar notificação in-app para supervisores
- **D-12:** Notificação via `App\Notifications\ToleranceExceeded`, criada síncrona no momento do salvamento da aferição
- **D-13:** Sem comando scheduled para este alerta — é tempo real, não batch

### 5. Histórico e Visualização

- **D-14:** Histórico de aferições exibido como **aba no DetailPage do equipamento** (dentro de EquipmentDetailPage, Phase 5). Diferente de Calibrações (que usou listagem filtrada)
- **D-15:** Aba "Aferições" no equipamento com timeline: data, operador, resultado (within/outside), link para detalhes
- **D-16:** Adicionalmente, botão "Aferir" na própria aba para iniciar nova aferição sem sair do equipamento

### 6. Permissões e Navegação

- **D-17:** Permissões: `afericoes.view`, `afericoes.create` (operador pode registrar), `afericoes.edit` (supervisor pode corrigir)
- **D-18:** Sidebar: categoria "Operações" → "Aferições" (ícone `pi pi-check-circle`, permissão `afericoes.view`). Rota: `/verifications/pending` (lista de pendentes)
- **D-19:** Aba "Aferições" no DetailPage do equipamento visível apenas com permissão `afericoes.view`

### the agent's Discretion
- Nomes específicos de controllers, services, stores (seguindo convenções dos módulos existentes)
- Ordem de implementação (backend DB → backend CRUD → frontend)
- Layout exato do formulário de aferição (campos, ordem, grid)
- Quantidade de dias para considerar "atrasado" na lista de pendentes (default: frequency excedida)
- Template da notificação in-app (texto, prioridade, link)
- Armazenamento do campo verification_frequency no equipamento (diretamente na tabela equipments ou tabela pivot)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — VERF-01, VERF-02
- `.planning/PROJECT.md` — Stack, key decisions, UUIDs, Sanctum

### Prior Phase Context
- `.planning/phases/08-calibracoes/08-CONTEXT.md` — Definição do boundary Calibração vs Aferição (D-06), padrão de permissões, sidebar, navegação
- `.planning/phases/05-equipamentos/05-CONTEXT.md` — Equipment model, categories (base para verification_templates vinculado à categoria)
- `.planning/phases/06-estoque/06-CONTEXT.md` — Padrão de movimentações como referência para registros imutáveis

### Codebase Maps
- `.planning/codebase/ARCHITECTURE.md` — Layers, data flow, controller/service pattern
- `.planning/codebase/CONVENTIONS.md` — Naming, imports, backend/frontend conventions
- `.planning/codebase/STACK.md` — Technology versions, package decisions

### Navigation & Routes
- `frontend/src/types/navigation.ts` — Sidebar structure (referência para adicionar Aferições)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **CalibrationService / CalibrationCertificateService** — Padrão de service layer com DB::transaction para criar registros compostos (verification + params)
- **CheckCalibrationDue** — Padrão de comando scheduled (não usado para alerta imediato, mas como referência de notificação in-app)
- **LoanCreateDialog** — Padrão de Dialog modal para criação de registros (reutilizável para formulário de aferição)
- **CalibrationCreateDialog** — Padrão de formulário com campos dinâmicos (reutilizável para parâmetros do template)
- **EquipmentDetailPage** — Onde a aba "Aferições" será adicionada (Phase 5)

### Established Patterns
- **CRUD module:** Migration compound → Models → Services → Controllers → Frontend types/service/store → UI pages
- **Permissions:** RolePermissionSeeder com middleware estático nos controllers
- **Notifications:** App\Notifications\* com criação via Notification::send()
- **Sidebar:** Categoria "Operações" com sub-módulos

### Integration Points
- **EquipmentDetailPage** — Adicionar nova aba "Aferições" (TabPanel)
- **Equipment Model** — Adicionar campo `verification_frequency` e relacionamento hasMany com Verifications
- **EquipmentCategory Model** — Adicionar relacionamento hasMany com VerificationTemplates

</code_context>

<specifics>
## Specific Ideas

- Interface de aferição deve ser rápida — operador registra várias aferições em sequência. Ideal: fluxo de "próximo equipamento" sem voltar à lista
- Resultado visual claro: verde para within, vermelho para outside_range, cinza para not_measured
- Tolerâncias podem ser assimétricas (tolerance_min diferente de tolerance_max)

</specifics>

<deferred>
## Deferred Ideas
- Aferição com foto do equipamento no momento da verificação — fase futura
- Aferição com assinatura digital do operador — fase futura
- Relatório de aferições (equipamentos com mais falhas, tendências) — Phase 12 (Relatórios)
- Alertas por email quando tolerância excedida — depende da infraestrutura de email
- Checklist de verificação com itens sim/não além de valores numéricos — fase futura

</deferred>

---

*Phase: 09-afericoes*
*Context gathered: 2026-07-25*
