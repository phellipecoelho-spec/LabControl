# Phase 09: Aferições - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-25
**Phase:** 09-afericoes
**Areas discussed:** Modelo de Parâmetros, Limites de Tolerância, Fluxo de Registro, Alerta de Tolerância, Histórico e Visualização

---

## Modelo de Parâmetros

| Option | Description | Selected |
|--------|-------------|----------|
| Tabela verification_params (1:N) | Linhas individuais: parameter_name, value, unit, result | |
| Templates + Params | Templates por categoria + params para valores registrados | ✓ |
| JSON flexível | Campo JSON na tabela verifications | |

**User's choice:** Templates + Params
**Notes:** Template vinculado à categoria do equipamento (FK equipment_category_id). Cada parâmetro no template já tem tolerance_min, tolerance_max, unit.

---

## Limites de Tolerância

| Option | Description | Selected |
|--------|-------------|----------|
| No template | tolerance_min/tolerance_max no verification_template | ✓ |
| Tabela separada | Tabela tolerance_limits com vínculo próprio | |

**User's choice:** No template
**Notes:** Tolerâncias nascem junto com o parâmetro no template. Simplifica o modelo e evita tabelas extras.

---

## Fluxo de Registro

| Option | Description | Selected |
|--------|-------------|----------|
| Lista de pendentes | Equipamentos que precisam de aferição hoje calculados automaticamente | ✓ |
| Seleção manual | Operador seleciona equipamento manualmente | |

**User's choice:** Lista de pendentes
**Notes:** Frequência definida por equipamento (daily/weekly/shift), não por categoria. Cálculo baseado em last_verification_at + frequency.

---

## Alerta de Tolerância

| Option | Description | Selected |
|--------|-------------|----------|
| Imediato + in-app | Alerta visual + notificação no momento do registro | ✓ |
| Batch diário | Comando scheduled varrendo aferições do dia | |

**User's choice:** Imediato + in-app
**Notes:** Notification::send() síncrona ao salvar aferição com outside_range. Destinatário: supervisores. Alerta visual no formulário.

---

## Histórico e Visualização

| Option | Description | Selected |
|--------|-------------|----------|
| Aba no DetailPage do equipamento | Timeline dentro do equipamento | ✓ |
| Página separada | Listagem global com filtros | |

**User's choice:** Aba no DetailPage do equipamento
**Notes:** Diferente da decisão de Calibrações (que usou listagem filtrada). Botão "Aferir" na própria aba. Aba visível apenas com permissão afericoes.view.

---

## the agent's Discretion

- Nomes específicos de controllers, services, stores
- Ordem de implementação
- Layout exato do formulário de aferição
- Quantidade de dias para considerar "atrasado"
- Template da notificação in-app
- Armazenamento do campo verification_frequency

## Deferred Ideas

- Aferição com foto do equipamento — fase futura
- Aferição com assinatura digital — fase futura
- Relatório de aferições — Phase 12
- Alertas por email — depende de infraestrutura
- Checklist de verificação sim/não — fase futura
