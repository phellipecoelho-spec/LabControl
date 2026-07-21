# Phase 7 Discussion Log: Empréstimos

**Date:** 2026-07-21
**Duration:** ~15 min
**Mode:** discuss (default)

## Areas Discussed

### 1. Modelo do Empréstimo
- **Decision:** 1 empréstimo = múltiplos equipamentos via tabela pivot `equipment_loan`
- **Decision:** Tomador é sempre funcionário cadastrado (FK users)
- **Decision:** 4 status: reserved, active, returned, cancelled
- **Decision:** Devolução parcial permitida (returned_at por item na pivot)
- **Decision:** Campos: borrowed_at, expected_return_at, returned_at, reason, destination, contact, notes, approved_by, created_by, borrower_id, status

### 2. Fluxo e Calendário (LOAN-02)
- **Decision:** Lista cronológica (DataTable com filtros), não calendário visual
- **Decision:** Colunas: Equipamento(s), Tomador, Data Retirada, Data Prevista, Status, Ações

### 3. Notificação de Atraso (LOAN-03)
- **Decision:** Laravel scheduled command diário → notificações in-app
- **Decision:** Sem email nesta fase

### 4. Padrão de Interface
- **Decision:** ListPage + DetailPage + criação por Dialog (sem FormPage separada)
- **Decision:** DetailPage com abas: Dados, Itens, Timeline
- **Decision:** Dialog de devolução na DetailPage para devolução parcial

## Deferred Ideas
- Empréstimos para externos
- Email notification
- Calendário visual
- Workflow de aprovação
- Relatórios de empréstimos (Phase 12)
- Assinatura digital
