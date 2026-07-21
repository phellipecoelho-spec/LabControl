# Phase 6: Estoque - Context

**Gathered:** 2026-07-20
**Status:** Ready for planning

<domain>
## Phase Boundary

Módulo de controle de estoque de insumos e peças — cadastro de itens com categorias próprias, movimentações de entrada e saída com tipos predefinidos, alerta visual + notificação de estoque mínimo, e página dedicada de movimentações.

**Requisitos cobertos:**
- INVT-01: Controle de estoque de insumos e peças
- INVT-02: Movimentações de entrada e saída
- INVT-03: Alertas de estoque mínimo

</domain>

<decisions>
## Implementation Decisions

### 1. Modelo de Dados
- **D-01:** Tabela `inventory_items` com campos: nome, descrição, quantidade_atual, unidade, estoque_minimo, lote, data_validade, localizacao_fisica, código (identificador interno)
- **D-02:** Tabela separada `inventory_categories` (não reusa categories dos equipamentos) — relacionamento belongsTo
- **D-03:** Vinculação com `suppliers` existente — cada item pertence a um fornecedor cadastrado
- **D-04:** Categorias planas (sem hierarquia), mesmo padrão das categorias de equipamentos
- **D-05:** Nenhuma tabela de locais separada — localização_fisica como campo texto

### 2. Movimentações
- **D-06:** Tabela separada `inventory_movements` para rastreabilidade completa
- **D-07:** Tipos de movimentação fixos predefinidos: `purchase` (compra), `consumption` (consumo), `adjustment` (ajuste manual), `disposal` (descarte), `return` (devolução)
- **D-08:** Campos da movimentação: tipo, quantidade, saldo_resultante, motivo (texto), responsável (user_id), item_id, observações
- **D-09:** Tipo `purchase` e `return` incrementam saldo; `consumption`, `disposal` e `adjustment` podem decrementar
- **D-10:** Saldo atual calculado via SUM das movimentações (não campo manual)

### 3. Alerta de Estoque Mínimo
- **D-11:** Ao registrar uma movimentação de saída que deixe o item com quantidade <= estoque_minimo, exibir toast de alerta imediatamente
- **D-12:** Indicador visual na DataTable (linha destacada em vermelho/laranja para itens críticos)
- **D-13:** Badge no sidebar com contagem de itens críticos será implementado no futuro (Dashboard / Phase 11)

### 4. Campos do Item
- **D-14:** Campos obrigatórios: nome, categoria, fornecedor, quantidade inicial, unidade, estoque mínimo
- **D-15:** Campos opcionais: lote, data de validade, localização física, descrição, código interno
- **D-16:** Unidades de medida: lista fixa (UN, KG, L, CX, M, M², M³, PC, PCT, CJ) — campo enum/varchar

### 5. Interface
- **D-17:** Mesmo padrão do módulo de Equipamentos (ListPage + FormPage + DetailPage)
- **D-18:** ListPage com DataTable: colunas Nome, Código, Categoria, Quantidade, Unidade, Estoque Mínimo, Fornecedor, Status (crítico/normal)
- **D-19:** FormPage com abas: Principal (dados básicos), Armazenamento (lote, validade, localização)
- **D-20:** DetailPage com abas: Dados do Item, Movimentações (tabela de histórico)
- **D-21:** Página separada de movimentações em `/inventory/movements` com DataTable de todas as movimentações do sistema, filtrável por item, tipo, período, responsável
- **D-22:** Registro de movimentação via Dialog modal na página de movimentações (ou diretamente na DetailPage)

### Agent's Discretion
- Nomes específicos de rotas, controllers, services seguindo convenções do Equipment module
- Ordem de implementação (backend DB → backend CRUD → frontend CRUD → movimentações)
- Índices do banco além dos obrigatórios (FKs)
- Layout exato de cada aba (campos, ordem, grid)
- Estratégia de validação de movimentações (saldo nunca negativo)
- Ícone e nome exato no sidebar para "Movimentações de Estoque"

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — INVT-01, INVT-02, INVT-03
- `.planning/PROJECT.md` — Stack, key decisions, UUIDs, Sanctum

### Prior Phase Context
- `.planning/phases/05-equipamentos/05-CONTEXT.md` — Padrão CRUD, migration compound, model traits, controller pattern, frontend module structure
- `.planning/phases/04-layout-navegacao/04-CONTEXT.md` — Sidebar categoria "Gestão", navegação por módulos, tipos de navegação
- `.planning/phases/03-usuarios-permissoes/03-CONTEXT.md` — LogsActivity trait, RBAC, permissões

### Codebase Maps
- `.planning/codebase/ARCHITECTURE.md` — Layers, data flow, controller/service pattern
- `.planning/codebase/CONVENTIONS.md` — Naming, imports, backend/frontend conventions

### Existing Code Patterns (Equipment Module)
- `backend/app/Models/Equipment.php` — Model pattern com UUIDs, SoftDeletes, LogsActivity, casts, fillable
- `backend/app/Http/Controllers/Api/V1/EquipmentController.php` — Full CRUD controller com permission middleware
- `backend/app/Http/Requests/StoreEquipmentRequest.php` — Form Request validation pattern
- `backend/app/Http/Resources/EquipmentResource.php` — API Resource with whenLoaded
- `backend/app/Services/EquipmentPhotoService.php` — Service layer for complex logic
- `backend/database/migrations/2026_07_19_000002_create_equipments_tables.php` — Compound migration pattern (multiple tables in one file)
- `backend/database/seeders/EquipmentSeeder.php` — Seeder pattern with reference data
- `backend/database/seeders/RolePermissionSeeder.php` — Permissions already seeded: estoque.view, estoque.create, estoque.edit, estoque.delete
- `backend/routes/api.php` — Route conventions (prefix v1, Sanctum, apiResource)

### Existing Code Patterns (Frontend)
- `frontend/src/modules/equipment/pages/EquipmentListPage.vue` — DataTable + filters + pagination pattern
- `frontend/src/modules/equipment/pages/EquipmentFormPage.vue` — Form page with tabs pattern
- `frontend/src/modules/equipment/pages/EquipmentDetailPage.vue` — Detail page with tabs pattern
- `frontend/src/modules/equipment/store/EquipmentStore.ts` — Pinia store with composition API
- `frontend/src/modules/equipment/services/EquipmentService.ts` — API service pattern
- `frontend/src/modules/equipment/types/equipment.ts` — TypeScript interfaces pattern
- `frontend/src/types/navigation.ts` — Sidebar module registration (Estoque já configurado em Gestão)
- `frontend/src/router/routes.ts` — Route definitions (rota /inventory já existe como placeholder)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **LogsActivity trait** — Log automático para mutações nos models InventoryItem e InventoryMovement
- **EquipmentController pattern** — CRUD com permission middleware, paginação, filtros, soft deletes
- **EquipmentStore pattern** — Pinia store com fetchAll, fetchById, create, update, destroy
- **PrimeVue DataTable** — Já usado em todas as listagens, reutilizar para InventoryItemListPage
- **PrimeVue Tabs** — Já usado em ProfilePage e EquipmentDetailPage, reutilizar na DetailPage
- **Module scaffold** — `frontend/src/modules/inventory/` já existe com pastas vazias (components, pages, routes, services, store, types)
- **Route placeholder** — `/inventory` já mapeado para `inventory.index`, mas aponta para PlaceholderPage

### Established Patterns
- **Backend CRUD**: Controller → FormRequest → Model (Equipment pattern)
- **Frontend CRUD**: Page → Store → Service → API (Equipment pattern)
- **UUIDs**: Primary keys UUID em todos os models
- **Soft deletes**: `softDeletes()` + `deleted_by` em tabelas de negócio
- **Auditoria**: Toda mutação logada via LogsActivity trait
- **Permissions**: Já seedadas (`estoque.view/create/edit/delete`), middleware via `$this->middleware()`
- **Migration**: Compound migration (múltiplas tabelas em um arquivo só)

### Integration Points
- `backend/routes/api.php` — Adicionar rotas `/api/v1/inventory-items`, `/api/v1/inventory-categories`, `/api/v1/inventory-movements`
- `backend/database/migrations/` — Criar migration compound para inventory_items + inventory_categories + inventory_movements
- `backend/database/seeders/RolePermissionSeeder.php` — Permissions já existem, verificar se precisa adicionar movimentações
- `frontend/src/modules/inventory/` — Popular pastas vazias com pages/, store/, services/, types/
- `frontend/src/router/routes.ts` — Substituir PlaceholderPage por InventoryItemListPage; adicionar rotas form, detail, movements
- `frontend/src/types/navigation.ts` — Adicionar rota de movimentações no sidebar (Operações) se aplicável
- `backend/app/Traits/LogsActivity.php` — Reutilizar para auditoria de inventory_items e inventory_movements

</code_context>

<specifics>
## Specific Ideas

- Interface seguindo mesmo padrão do módulo de Equipamentos para consistência visual
- Página de movimentações com filtros por item, tipo, período e responsável
- Alerta de estoque mínimo via toast do PrimeVue no momento da movimentação
- Campo de saldo calculado por query agregada (SUM das movimentações), não armazenado

</specifics>

<deferred>
## Deferred Ideas

- **Badge no sidebar** com contagem de itens críticos — Phase 11 (Dashboard)
- **Notificações reais** (email/in-app) para estoque crítico — fase futura
- **Código de barras** para itens — pode ser adicionado depois como campo extra
- **NCM** para itens — pode ser adicionado depois se necessário para relatórios fiscais
- **Importação em lote** de itens de estoque via planilha — fase futura
- **Transferência entre almoxarifados** — multi-laboratório (v2+)

</deferred>

---

*Phase: 06-Estoque*
*Context gathered: 2026-07-20*
