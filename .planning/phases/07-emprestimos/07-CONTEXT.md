# Phase 7: Empréstimos - Context

**Gathered:** 2026-07-21
**Status:** Ready for planning

<domain>
## Phase Boundary

Módulo de controle de empréstimos de equipamentos — registro de retirada e devolução de equipamentos para funcionários internos, com suporte a múltiplos equipamentos por empréstimo, devolução parcial, agenda de reservas em lista cronológica, e notificação automática de atrasos.

**Requisitos cobertos:**
- LOAN-01: Usuário pode registrar empréstimos de equipamentos
- LOAN-02: Usuário pode visualizar agenda de reservas
- LOAN-03: Sistema notifica quando devolução está atrasada

</domain>

<decisions>
## Implementation Decisions

### 1. Modelo de Dados

- **D-01:** 1 empréstimo pode conter múltiplos equipamentos via tabela pivot `equipment_loan` (loan_id, equipment_id, returned_at, notes)
- **D-02:** Tomador é sempre um funcionário cadastrado (FK borrower_id → users). Sem suporte a pessoas externas nesta fase
- **D-03:** Status do empréstimo: `reserved` (agendado), `active` (com o tomador), `returned` (devolvido), `cancelled` (cancelado)
- **D-04:** Devolução parcial permitida — cada item na pivot tem `returned_at` individual. O loan só transita para `returned` quando TODOS os itens tiverem sido devolvidos
- **D-05:** Campos da tabela `loans`: borrower_id, status, borrowed_at, expected_return_at, returned_at (nullable — preenchido quando todos itens devolvidos), reason (motivo), destination (destino/setor/lab), contact (contato), notes, approved_by (FK users, nullable), created_by (FK users)

### 2. Agenda e Visualização (LOAN-02)

- **D-06:** Visualização da agenda em lista cronológica (DataTable com filtros por período, status, equipamento). Sem calendário mensal ou kanban nesta fase
- **D-07:** ListPage com colunas: Equipamento(s), Tomador, Data Retirada, Data Prevista Devolução, Status, Ações

### 3. Notificação de Atraso (LOAN-03)

- **D-08:** Notificação in-app via Laravel scheduled command diário que verifica empréstimos com `expected_return_at < now()` e `status = active`, criando registros na tabela `notifications` para administradores e supervisores
- **D-09:** Sem notificação por email nesta fase (infraestrutura de email pode ser adicionada futuramente)

### 4. Interface

- **D-10:** Padrão ListPage + DetailPage + criação por Dialog modal (sem FormPage separada)
- **D-11:** ListPage: DataTable com filtros por período, status, equipamento. Botão "Novo Empréstimo" abre Dialog
- **D-12:** DetailPage com abas: Dados do Empréstimo (campos + tomador), Itens (lista de equipamentos com status de devolução), Timeline (histórico de status)
- **D-13:** Dialog de criação: selecionar tomador (user), selecionar múltiplos equipamentos (MultiSelect), datas (retirada e prevista devolução), motivo, destino, observações. Aprovação opcional (campo aprovador)
- **D-14:** Dialog de devolução (na DetailPage): confirmar data de devolução, observações por item

### 5. Permissões e Navegação

- **D-15:** Permissões já seedadas: `emprestimos.view`, `emprestimos.create`, `emprestimos.edit`, `emprestimos.finalizar`. Middleware de permissão nos controllers (mesmo padrão Equipment/Inventory) — conforme RolePermissionSeeder
- **D-16:** Sidebar já configurada: categoria "Operações" → "Empréstimos" (pi-share-alt, emprestimos.view, loans.index)
- **D-17:** Rotas: `/loans` (index), `/loans/create` (dialog via store — mantida como ação na lista), `/loans/{id}` (show)

### 6. Campos do Equipamento na Pivot

- **D-18:** A tabela pivot `equipment_loan` armazena: equipment_id, loan_id, returned_at (nullable), notes (observações por item na devolução)
- **D-19:** Ao criar um empréstimo, todos os equipment_loan.returned_at iniciam como null. Ao devolver, preenche individualmente

### Agent's Discretion
- Nomes específicos de rotas, controllers, services seguindo convenções dos módulos existentes
- Ordem de implementação (backend DB → backend CRUD → frontend CRUD + notificação)
- Índices do banco além dos obrigatórios (FKs)
- Layout exato de cada aba da DetailPage (campos, ordem, grid)
- Estratégia de validação (status transition rules, impedir empréstimo de equipamento já emprestado)
- Template da notificação in-app (texto, prioridade, link)
- Ícone e label exatos para os botões de ação (Devolver, Cancelar)

</decisions>

<deferred>
## Deferred Ideas
- Empréstimos para pessoas externas (sem cadastro no sistema) — fase futura
- Notificação por email de atraso — depende da infraestrutura de email/configuração SMTP
- Calendário visual mensal — fase futura de UI
- Aprovação workflow (solicitar → aprovar → retirar) — fase futura
- Relatórios de empréstimos (mais emprestados, taxas de atraso, etc.) — Phase 12 (Relatórios)
- Assinatura digital no momento da retirada — fase futura

</deferred>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — LOAN-01, LOAN-02, LOAN-03
- `.planning/PROJECT.md` — Stack, key decisions, UUIDs, Sanctum

### Prior Phase Context
- `.planning/phases/05-equipamentos/05-CONTEXT.md` — Padrão CRUD, migration compound, model traits, controller pattern, frontend module structure (Equipment é a entidade central sendo emprestada)
- `.planning/phases/06-estoque/06-CONTEXT.md` — Padrão de movimentações, inventory_movements como referência para registros imutáveis
- `.planning/phases/04-layout-navegacao/04-CONTEXT.md` — Sidebar categoria "Operações", navegação por módulos
- `.planning/phases/03-usuarios-permissoes/03-CONTEXT.md` — LogsActivity trait, RBAC, permissões

### Codebase Maps
- `.planning/codebase/ARCHITECTURE.md` — Layers, data flow, controller/service pattern
- `.planning/codebase/CONVENTIONS.md` — Naming, imports, backend/frontend conventions

### Navigation & Routes (já configurados)
- `frontend/src/router/routes.ts` — Route `loans.index` with placeholder
- `frontend/src/types/navigation.ts` — Sidebar entry for Empréstimos in Operações
- `backend/database/seeders/RolePermissionSeeder.php` — Permissions emprestimos.{view,create,edit,finalizar} already seeded

</canonical_refs>

<folded_todos>
## Folded Todos
Nenhum — nenhum TODO pendente correspondeu à Phase 7.

</folded_todos>
