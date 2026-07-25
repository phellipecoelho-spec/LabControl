# Phase 10: Manutenções - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-25
**Phase:** 10-manutencaoes
**Areas discussed:** Tipos e Status, Abertura da Ordem, Agendamento Preventivo, Fechamento da Ordem, Histórico e Timeline, Permissões

---

## Tipos e Status

**Question 1:** Como diferenciar manutenção preventiva da corretiva?

| Option | Description | Selected |
|--------|-------------|----------|
| Tipo + mesmo fluxo | Campo `type` (preventive/corrective) na ordem, mesmo workflow de status para ambas. Simplifica o modelo. | ✓ |
| Tipos separados | Tabelas ou modelos separados para preventiva e corretiva. | |

**User's choice:** Tipo + mesmo fluxo
**Notes:** Único modelo com campo type. Preventiva e corretiva compartilham o mesmo workflow de status.

**Question 2:** Qual workflow de status?

| Option | Description | Selected |
|--------|-------------|----------|
| Aberta → Em Andamento → Concluída / Cancelada | 4 status: open, in_progress, completed, cancelled | ✓ |
| Aberta → Em Andamento → Concluída | 3 status, sem cancelamento | |
| Aberta → Designada → Em Andamento → Concluída / Cancelada | 5 status com assigned | |

**User's choice:** Aberta → Em Andamento → Concluída / Cancelada

---

## Abertura da Ordem

| Option | Description | Selected |
|--------|-------------|----------|
| Equipamento (obrigatório) | FK equipment_id | ✓ |
| Tipo (preventiva/corretiva) | Já decidido | ✓ |
| Prioridade | Enum: low, medium, high, critical | ✓ |
| Descrição/Problema | Texto livre | ✓ |
| Técnico Responsável | FK users — opcional na abertura | |
| Data Agendada | Data prevista para execução | ✓ |

**User's choice:** Equipamento, Tipo, Prioridade, Descrição, Data Agendada
**Notes:** Técnico não é preenchido na abertura — designado depois via edição.

---

## Agendamento Preventivo

| Option | Description | Selected |
|--------|-------------|----------|
| Interval-based (como Calibrações) | Cada preventiva registra intervalo. Ao concluir, calcula próxima. | ✓ |
| Agenda fixa | Datas específicas marcadas manualmente. | |

**User's choice:** Interval-based (como Calibrações)
**Notes:** interval_value + interval_unit. Ao concluir, next_due_at calculado automaticamente. Se next_due_at existe, criar nova ordem preventiva com scheduled_date = next_due_at.

---

## Fechamento da Ordem

| Option | Description | Selected |
|--------|-------------|----------|
| Parecer Técnico (texto) | Descrição do que foi feito | ✓ |
| Peças Utilizadas | Link com InventoryItem (Estoque) | ✓ |
| Tempo Gasto (horas) | Duração total | ✓ |
| Custo (R$) | Custo da manutenção | ✓ |
| Data de Conclusão | Automática com ajuste manual | ✓ |

**User's choice:** Todos os itens selecionados
**Notes:** Peças utilizadas requer tabela pivot maintenance_order_parts. Descontar do estoque no fechamento.

---

## Histórico e Timeline

| Option | Description | Selected |
|--------|-------------|----------|
| Aba no EquipmentDetailPage | Similar a Aferições | ✓ |
| Página de listagem dedicada | Página própria /maintenance | |
| Ambos | Aba + página dedicada | |

**User's choice:** Aba no EquipmentDetailPage

**Follow-up (Página Principal):** A rota /maintenance deve ser ListPage real ou redirecionar?

| Option | Description | Selected |
|--------|-------------|----------|
| ListPage real | DataTable com filtros | ✓ |
| Redirecionar para equipamentos | Apenas aba no equipamento | |

**User's choice:** ListPage real com DataTable e filtros

---

## Permissões

| Option | Description | Selected |
|--------|-------------|----------|
| manutencoes.view | Visualizar ordens e histórico | ✓ |
| manutencoes.create | Abrir nova ordem | ✓ |
| manutencoes.edit | Editar dados da ordem | ✓ |
| manutencoes.concluir | Concluir/cancelar ordem | ✓ |

**User's choice:** Todas as 4 permissões selecionadas

---

## Notificações

| Option | Description | Selected |
|--------|-------------|----------|
| Sim, notificação in-app | Notificar técnico + supervisores | ✓ |
| Não nesta fase | Apenas registro | |

**User's choice:** Sim, notificação in-app
**Notes:** Notification class MaintenanceOrderCreated, database channel, notificar supervisores (manutencoes.edit). Disparar no momento da criação.

---

## the agent's Discretion

- Nomes específicos de controllers, services, stores
- Ordem de implementação (backend DB → backend CRUD → frontend)
- Layout exato dos formulários (abertura, fechamento)
- Template da notificação in-app (texto, prioridade, link)
- Ícones específicos para botões/ações
- Mecanismo de criação automática de ordem preventiva (evento vs service)

## Deferred Ideas

- Alerta de manutenção preventiva vencida (scheduled command)
- Checklist de itens de verificação na preventiva
- Anexar fotos/laudos à ordem
- Workflow de aprovação de ordens
- Dashboard de manutenções (Phase 11 ou 12)
