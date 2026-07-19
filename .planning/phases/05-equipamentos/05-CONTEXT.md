# Phase 5: Equipamentos - Context

**Gathered:** 2026-07-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Cadastro completo de equipamentos como entidade central do sistema — cada equipamento é o nó principal da rastreabilidade, conectado a categorias, fabricantes, fornecedores, anexos fotográficos e histórico de alterações.

**Requisitos cobertos:**
- EQUIP-01: Cadastro completo de equipamentos com dados técnicos e administrativos
- EQUIP-02: Gerenciamento de categorias, fabricantes e fornecedores
- EQUIP-03: Anexos fotográficos (fotos até 5MB cada)
- EQUIP-04: Histórico de alterações do equipamento

</domain>

<decisions>
## Implementation Decisions

### 1. Modelo de Dados
- **D-01:** Tabela única `equipments` com todos os campos da ficha técnica (~25 colunas). Sem split em tabela separada de especificações
- **D-02:** Categorias em tabela `categories` plana (sem hierarquia), relacionamento belongsTo
- **D-03:** Fabricantes em tabela `manufacturers` separada (nome, país, site, logo)
- **D-04:** Fornecedores em tabela `suppliers` separada (nome, CNPJ, contato, endereço)
- **D-05:** Localização como campo texto simples (`location` varchar) no próprio equipamento, sem tabela de locais

### 2. Interface de Cadastro
- **D-06:** Lista de equipamentos em DataTable PrimeVue com colunas: Nome, Patrimônio, Categoria, Fabricante, Série, Localização, Status, Última Calibração (indicador), Ações
- **D-07:** Criação/edição em página dedicada com abas (não modal)
- **D-08:** Abas da página de equipamento: Principal (dados básicos), Localização, Técnica (especificações), Arquivos (fotos), Logs (histórico)
- **D-09:** Campos obrigatórios no cadastro: nome, categoria, fabricante, número de série, localização

### 3. Upload de Anexos
- **D-10:** Apenas fotos nesta fase (sem manuais, certificados ou outros anexos). Manuais serão adicionados em fase futura
- **D-11:** Limite de 5MB por foto, formatos jpg/png/webp
- **D-12:** Fotos armazenadas em `backend/storage/app/public/equipment/photos/` com symlink público (padrão Laravel)

### 4. Histórico de Alterações
- **D-13:** Reutilizar `LogsActivity` trait existente (Fase 3) para log automático de mutações no model Equipment
- **D-14:** Visualização do histórico na aba "Logs" da página de equipamento, reutilizando Timeline do AuditLogsPage.vue filtrada por subject_type=Equipment

### Agent's Discretion
- Número máximo de fotos por equipamento (sugerido: 5-10)
- Nomes específicos de rotas, controllers, services e stores seguindo convenções existentes
- Ordem de implementação dos sub-módulos (backend CRUD → frontend CRUD → upload → histórico)
- Índices do banco além dos obrigatórios (FKs, busca textual)
- Layout exato de cada aba (campos, ordem, grid)
- Estratégia de compressão/redimensionamento de fotos no upload

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements & Project
- `.planning/REQUIREMENTS.md` — EQUIP-01, EQUIP-02, EQUIP-03, EQUIP-04
- `.planning/PROJECT.md` — Stack, key decisions, UUID, Sanctum

### Prior Phase Context
- `.planning/phases/03-usuarios-permissoes/03-CONTEXT.md` — LogsActivity trait, padrão CRUD com DataTable + Dialog
- `.planning/phases/04-layout-navegacao/04-CONTEXT.md` — Sidebar categoria "Gestão", navegação por módulos

### Codebase Maps
- `.planning/codebase/ARCHITECTURE.md` — Layers, data flow, controller/service/repository pattern
- `.planning/codebase/CONVENTIONS.md` — Naming, imports, backend/frontend conventions

### Existing Code Patterns (Backend)
- `backend/app/Models/User.php` — Model pattern com UUIDs, casts, Fillable, Hidden
- `backend/app/Http/Controllers/Api/V1/UserController.php` — Controller CRUD pattern com Form Requests, Resources
- `backend/app/Models/Role.php` — Model com is_system, permissions relationship
- `backend/app/Models/Permission.php` — Model com group field
- `backend/app/Models/ActivityLog.php` — Model de auditoria para histórico
- `backend/app/Traits/LogsActivity.php` — Trait reutilizável para log automático de mutações
- `backend/app/Services/AvatarService.php` — Serviço de upload de arquivos (referência para upload de fotos)

### Existing Code Patterns (Frontend)
- `frontend/src/stores/users.ts` — Pinia store CRUD pattern com fetchAll, create, update, destroy
- `frontend/src/modules/admin/pages/UsersPage.vue` — DataTable + Dialog pattern, filtros, paginação
- `frontend/src/modules/admin/pages/AuditLogsPage.vue` — Timeline visual para histórico
- `frontend/src/modules/admin/components/UserFormDialog.vue` — Formulário em Dialog com campos organizados

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **LogsActivity trait** — Log automático de qualquer model com bootTrait; reutilizar em Equipment
- **AvatarService** — Serviço de upload com storage local e symlink público; adaptar para fotos de equipamento
- **PrimeVue DataTable + Dialog** — Padrão CRUD já estabelecido em UsersPage
- **PrimeVue Timeline** — Componente já usado em AuditLogsPage para exibir histórico
- **PrimeVue Tabs** — Componente já usado em ProfilePage (Informações/Senha/Avatar)
- **AuthStore.hasPermission()** — Controle de permissões já integrado

### Established Patterns
- **Backend CRUD**: Controller → FormRequest → Service → Model (UserController pattern)
- **Frontend CRUD**: Page → Store → Service (Axios) → API (UsersPage pattern)
- **UUIDs**: Primary keys UUID em todos os models
- **Soft deletes**: `softDeletes()` + `deleted_by` em todas as tabelas de negócio
- **Auditoria**: Toda mutação logada via LogsActivity trait
- **API Routes**: Prefixo `/api/v1/`, Sanctum middleware

### Integration Points
- `backend/routes/api.php` — Criar rotas `/api/v1/equipments`, `/api/v1/categories`, `/api/v1/manufacturers`, `/api/v1/suppliers`
- `frontend/src/modules/equipment/` — Módulo existe como scaffold vazio, popular com pages/, components/, services/, store/
- `frontend/src/types/navigation.ts` — Adicionar rota de equipamentos na categoria "Gestão"
- `frontend/src/router/routes.ts` — Adicionar rotas do módulo equipment com lazy loading
- `backend/database/migrations/` — Criar migrations para equipments, categories, manufacturers, suppliers

</code_context>

<specifics>
## Specific Ideas

- Interface inspirada em padrão de página dedicada com abas (como perfil do GitHub ou detalhes de repositório)
- Aba "Logs" reutiliza o componente Timeline existente com filtro automático por equipamento
- Tabela de equipamentos com coluna de status visual (ícone verde/vermelho para calibração)

</specifics>

<deferred>
## Deferred Ideas

- **Upload de manuais** — EQUIP-03 menciona manuais, mas decidido adiar para fase futura
- **Categorias hierárquicas** — Pode ser adicionado depois se necessário, categorias planas por enquanto
- **QR Code para equipamentos** — Geração de QR code com link direto para ficha do equipamento (fase futura)
- **Tabela de locais** — Se localização padronizada for necessária no futuro, migrar de campo texto para tabela
- **Importação em lote** — Importar equipamentos via planilha (fase futura)

</deferred>

---

*Phase: 05-Equipamentos*
*Context gathered: 2026-07-19*