# Phase 6: Estoque - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-20
**Phase:** 06-Estoque
**Areas discussed:** Estrutura do modelo, Movimentações, Alerta de estoque mínimo, Interface e campos

---

## 1. Estrutura do Modelo

| Option | Description | Selected |
|--------|-------------|----------|
| Tabela única inventory_items | Campos simples sem tabelas auxiliares | |
| Com categorias separadas | Inventory_items + inventory_categories | ✓ |
| Com categorias + fornecedores | Inventory_items + categories + relationship com suppliers | |

**Q1 - Categorias próprias ou reusar?**

| Option | Description | Selected |
|--------|-------------|----------|
| Tabela separada inventory_categories | Cada módulo tem suas categorias | ✓ |
| Reusar categories existente | Mesma tabela de categorias de equipamentos | |

**Q2 - Fornecedor vinculado?**

| Option | Description | Selected |
|--------|-------------|----------|
| Campo texto livre | Nome digitado manualmente | |
| Vinculado ao suppliers existente | belongsTo com tabela suppliers | ✓ |

**User's choice:** Inventory_items + inventory_categories separada + belongsTo suppliers
**Notes:** Categorias planas, sem hierarquia. Padrão consistente com categories de equipamentos.

---

## 2. Movimentações

| Option | Description | Selected |
|--------|-------------|----------|
| Tabela separada inventory_movements | Cada entrada/saída é um registro | ✓ |
| Apenas campos no item | Sem rastreabilidade individual | |
| Tabela + tipos predefinidos | Enum de tipos + rastreabilidade | |

**Q3 - Tipos predefinidos ou texto livre?**

| Option | Description | Selected |
|--------|-------------|----------|
| Tipos fixos predefinidos | purchase, consumption, adjustment, disposal, return | ✓ |
| Texto livre | Campo motivo aberto | |

**User's choice:** Inventory_movements com tipos predefinidos (purchase, consumption, adjustment, disposal, return). Saldo calculado por SUM.
**Notes:** Purchase e return incrementam saldo; consumption, disposal e adjustment decrementam.

---

## 3. Alerta de Estoque Mínimo

| Option | Description | Selected |
|--------|-------------|----------|
| Indicador visual na lista | Destaque vermelho na DataTable | |
| Badge no sidebar | Contagem de itens críticos no menu | |
| Notificação ao registrar | Toast ao fazer saída crítica | ✓ |
| Dashboard + lista + notificação | Combinação completa | |

**User's choice:** Toast/notificação no momento da movimentação + indicador visual na lista. Badge no sidebar adiado para Phase 11 (Dashboard).
**Notes:** Foco no alerta imediato durante operação. Indicador visual complementar na listagem.

---

## 4. Interface e Campos

| Option | Description | Selected |
|--------|-------------|----------|
| Campos básicos | Nome, categoria, fornecedor, quantidade, unidade, estoque mínimo | |
| Básicos + lote/validade | Adiciona lote, validade, localização física | ✓ |
| Completo | Adiciona código de barras, NCM, número de série | |

**Q5 - Formato da UI?**

| Option | Description | Selected |
|--------|-------------|----------|
| Mesmo padrão dos Equipamentos | ListPage + FormPage + DetailPage | ✓ |
| ListPage com Dialog | Criação/edição em modal | |
| Lista + FormPage sem detail | Sem página de detalhes | |

**Q6 - Onde ficam as movimentações?**

| Option | Description | Selected |
|--------|-------------|----------|
| Aba na DetailPage | Histórico dentro do detalhe do item | |
| Página separada | DataTable geral filtrável | ✓ |
| Ambos | Aba + página geral | |

**User's choice:** Padrão Equipamentos (List + Form + Detail) + página separada de movimentações. Campos: básicos + lote + validade + localização.
**Notes:** Unidades de medida: lista fixa (UN, KG, L, CX, M, M², M³, PC, PCT, CJ). Página de movimentações filtrável por item, tipo, período, responsável.

---

## the agent's Discretion

- Nomes específicos de rotas, controllers, services seguindo convenções do Equipment module
- Ordem de implementação (backend DB → backend CRUD → frontend CRUD → movimentações)
- Índices do banco além dos obrigatórios (FKs)
- Layout exato de cada aba (campos, ordem, grid)
- Estratégia de validação de movimentações (saldo nunca negativo)
- Ícone e nome exato no sidebar para "Movimentações de Estoque"

## Deferred Ideas

- **Badge no sidebar** com contagem de itens críticos — Phase 11 (Dashboard)
- **Notificações reais** (email/in-app) para estoque crítico — fase futura
- **Código de barras** para itens — pode ser adicionado depois como campo extra
- **NCM** para itens — pode ser adicionado depois se necessário para relatórios fiscais
- **Importação em lote** de itens de estoque via planilha — fase futura
- **Transferência entre almoxarifados** — multi-laboratório (v2+)
