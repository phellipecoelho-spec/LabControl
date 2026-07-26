---
phase: 10-manutencaoes
verified: 2026-07-25T23:55:00Z
status: human_needed
score: 17/17 must-haves verified
behavior_unverified: 0
overrides_applied: 0
gaps: []
human_verification:
  - test: "Acessar /maintenance e verificar DataTable com filtros (equipamento, tipo, status, prioridade, data)"
    expected: "Toolbar com filtros Select de equipamento, tipo (preventive/corrective), status, prioridade e date range. DataTable paginada com colunas Equipamento, Tipo (Tag verde=preventiva, azul=corretiva), Status (Tag colorido), Prioridade (Tag com severidade), Data Agendada, Técnico, Ações."
    why_human: "Layout PrimeVue DataTable e filtros combinados requerem verificação visual"
  - test: "Criar ordem de manutenção via botão 'Nova Manutenção' na página /maintenance"
    expected: "Dialog 'Nova Ordem de Manutenção' abre com campos: Equipment (Select com busca), Type (Preventiva/Corretiva), Priority (Baixa/Média/Alta/Crítica), Description (TextArea), Scheduled Date (DatePicker). Ao selecionar tipo 'Preventiva', campos Interval Value + Interval Unit aparecem. Ao salvar, toast 'Ordem de manutenção criada com sucesso' e ordem aparece na DataTable."
    why_human: "Fluxo completo de criação de ordem com campos condicionais requer verificação visual"
  - test: "Concluir ordem de manutenção com peças utilizadas"
    expected: "Dialog 'Concluir Manutenção' abre com campos: Parecer Técnico, Tempo Gasto (h), Custo (R$), Data Conclusão. Seção 'Peças Utilizadas' permite Adicionar Peça com select de InventoryItem + Quantidade + Custo Unitário + botão Remover. Ao salvar, ordem muda status para 'Concluída', toast 'Ordem concluída com sucesso'."
    why_human: "Fluxo de conclusão com lista dinâmica de peças requer verificação visual"
  - test: "Verificar aba Manutenções (tab 6) no EquipmentDetailPage"
    expected: "Aba 'Manutenções' visível como tab 6 no detalhe do equipamento, com DataTable paginada mostrando histórico (Data Abertura, Tipo, Status, Prioridade, Técnico, Conclusão). Linhas expansíveis para detalhes (descrição, resolução, tempo, custo, peças). Botão 'Nova Manutenção' no topo da aba."
    why_human: "Integração de aba no EquipmentDetailPage com componente compartilhado requer verificação visual"
  - test: "Conferir sidebar — Operações → Manutenções visível com permissão manutencoes.view"
    expected: "Sidebar 'Operações' mostra item 'Manutenções' com ícone pi-wrench apenas quando usuário tem permissão manutencoes.view. Ao clicar, navega para /maintenance"
    why_human: "Gating de permissão no sidebar requer verificação visual com diferentes roles"
behavior_unverified_items: []
---

# Phase 10: Manutenções — Verification Report

**Phase Goal:** Implementar o módulo de Ordens de Manutenção preventiva e corretiva:
- MAINT-01: Usuário pode abrir ordens de manutenção
- MAINT-02: Sistema mantém histórico de manutenções preventivas e corretivas

**Verified:** 2026-07-25T23:55:00Z
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | MAINT-01: Migration com tabelas maintenance_orders e maintenance_order_parts (D-01, D-03, D-05) | ✓ VERIFIED | `2026_07_25_100001_create_maintenance_tables.php` cria ambas tabelas com UUID, FKs, softDeletes, índices compostos. Migration executada com sucesso no migrate:fresh |
| 2 | MAINT-01: Modelo único com campo type (preventive/corrective) e status workflow (open→in_progress→completed|cancelled) (D-01, D-02) | ✓ VERIFIED | `MaintenanceType` enum (Preventive/Corrective), `MaintenanceStatus` enum com `canTransitionTo()`. 17 unit tests cobrem todas transições |
| 3 | MAINT-01: Usuário pode registrarcriação de ordens via POST /api/v1/maintenance-orders | ✓ VERIFIED | `MaintenanceOrderController.store()` cria via `MaintenanceService.create()` com equipamento, tipo, prioridade, descrição, scheduled_date. Teste `test_store_creates_order_with_valid_data` PASSING |
| 4 | MAINT-01: Ordem pode ser concluída com parecer técnico, horas, custo e peças (D-09) | ✓ VERIFIED | `MaintenanceService.complete()` aceita resolution, time_spent, cost, parts[]. `CompleteMaintenanceOrderRequest` valida. Teste `test_complete_with_parts_attaches_pivot_records` PASSING |
| 5 | MAINT-01: Ordens preventivas calculam`next_due_at` e auto-criam próxima ordem ao concluir (D-10) | ✓ VERIFIED | `MaintenanceService.complete()` hashas `calculateNextDue()` + `createNextPreventive()`. Teste `test_preventive_complete_auto_creates_next_order` PASSING |
| 6 | MAINT-01: Cancelamento de ordem com motivo opcional | ✓ VERIFIED | `MaintenanceOrderController.cancel()` com reason, via `MaintenanceService.cancel()`. Teste `test_cancel_transitions_to_cancelled` PASSING |
| 7 | MAINT-01: Notificação in-app ao criar ordem (D-08, D-18) | ✓ VERIFIED | `MaintenanceOrderCreated` notification via database channel. Disparada em `MaintenanceOrderController.store()` para usuários com `manutencoes.edit`. Teste `test_store_dispatches_notification_to_supervisors` PASSING |
| 8 | MAINT-01: Permissões implementadas (manutencamencoes.view/create/edit/concluir) (D-14) | ✓ VERIFIED | `RolePermissionSeeder` com 4 permissões e atribuição de roles corretas. Controller usa `permission:manutencoes.X`. Sidebar/item de tab gated por `hasPermission()` |
| 9 | MAINT-02: Histórico de manutenções exibido como aba no EquipmentDetailPage (abe 6) (D-12) | ✓ VERIFIED | `EquipmentDetailPage.vue` — TabPanel value="6" com `MaintenanceHistoryTab` gated por `manutencoes.view`. Componente com paginação lazy rows expansíveis |
| 10 | MAINT-02: Listagem dedicada em /maintenance com filtros (equipamento, tipo, status, prioridade, data) (D-13) | ✓ VERIFIED | `MaintenanceListPage.vue` substitui PlaceholderPage. Toolbar com filtros + DataTable com ações (ver/concluir/cancelar). Rota em routes.ts |
| 11 | MAINT-02: Service frontend com 8 métodos cobrindo todos endpoints | ✓ VERIFIED | `MaintenanceService.ts` com list/getById/create/update/delete/complete/cancel/getHistoryByEquipment |
| 12 | MAINT-02: TypeScript types matching API Resource | ✓ VERIFIED | `maintenance.ts` com MaintenanceOrder, MaintenanceOrderPart, form types, constantes com labels PT |
| 13 | MAINT-02: Frontend store com Pinia Composition API e evento `maintenance-saved` | ✓ VERIFIED | `MaintenanceStore.ts` com fetchAll/fetchById/create/complete/cancel + dispatchEvent 'maintenance-saved' |
| 14 | Bound4: Manutenção não usa comando scheduledpara alertas (D-19) | ✓ VERIFIED | Nenhum comando schedule registrado. Alerta de vencimento difadido paraassess futuras. Cálculo na conclusão via D-10 |
| 15 | MOT-02: Sidebar já scaffolded com pi-wrench (D-15) | ✓ VERIFIED | `navigation.ts` — category "Operações" → "Manutenções" com ícone pi-wrench, permissão manutencoes.view, rota maintenance.index |
| 16 | MAINT-01/02: DB Seeder população dados de ordens em todos status/tipos/prioridades | ✓ VERIFIED | `MaintenanceSeeder` com 55 ordens (20 randevos, 15 completas, 5 em andamento, 5 canceladas, 5 preventivas + 2 abertas preventivas + 3 urgentes). Execução sem erros |
| 17 | MAINT-02: Frontend labels e mensagens em português | ✓ VERIFIED | Todos componentes Vueusam labels PT: "Nova Ordem de Manutenção", "Concluir Manutenção", "Histórico de Manutenções", "Parecer Técnico", "Peças Utilizadas", constantes com MAINTENANCE_TYPE_OPTIONS em PT |

## Test Suite Status

| Suite | Tests | Assertions | Status |
|-------|-------|------------|--------|
| MaintenanceServiceTest (Unit) | 17 | 45 | ✓ PASSING |
| MaintenanceOrderControllerTest (Feature) | 15 | 46 | ✓ PASSING |
| Full Backend Suite | 76 | 190 | ✓ PASSING |

### Running Tests
```bash
docker exec labcontrol-php php artisan test --filter=MaintenanceServiceTest --stop-on-failure
docker exec labcontrol-php php artisan test --filter=MaintenanceOrderControllerTest --stop-on-failure
docker exec labcontrol-php php artisan test --stop-on-failure
```

## Open Issues

### No Code Issues

Automated verification found zero code issues — 17/17 must-haves confirmed by code inspection and 76 passing tests.

### Manual Verification Required (5 items)

5 visual/behavioral items require human UAT:
1. DataTable layout + filtros na /maintenance
2. Fluxo completo de criação de ordem (dialog com campos condicionais)
3. Fluxo de conclusão com lista dinâmica de peças
4. Tab Manutenções (tab 6) no EquipmentDetailPage
5. Sidebar gating por permissão

Use `/gsd-verify-work 10` to run through the items interactively.

## Threat Register Status

| Threat ID | Category | Disposition | Status |
|-----------|----------|-------------|--------|
| T-10-01 | Tampering — status transition | Mitigated: canTransitionTo() | ✓ |
| T-10-02 | Tampering — mass assignment | Mitigated: $fillable whitelist | ✓ |
| T-10-03 | Information Disclosure — OR | Mitigated: permission middleware | ✓ |
| T-10-04 | Renunciation — soft-delete audit | Mitigated: LogsActivity trait | ✓ |
| T-10-05 | Tampering — invalid parts | Counter: FormRequest validation | ✓ |
| T-10-06 | Information Disclosure — all orders | Accepted (fine-grained scope future) | ⚡ |
| T-10-07 | Tampering — invalid completion | Counter: canTransitionTo() + status check | ✓ |
| T-10-08 | Denial of Service — infinite preventive orders | Mitigated: single on-next per complete | ✓ |
| T-10-SC | Tampering — supply chain | Counter: No new packages | ✓ |