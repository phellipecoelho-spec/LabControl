# Phase 12: Relatórios - Context

**Gathered:** 2026-07-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Geração de relatórios pré-definidos em PDF, Excel e CSV para os módulos principais do sistema. Relatórios tabulares com filtros por período e status, acessíveis via página centralizada de relatórios. Geração síncrona server-side com download direto.

**Requisitos cobertos:** REPT-01 (relatórios em PDF/Excel/CSV), REPT-02 (exportação de dados)
</domain>

<decisions>
## Implementation Decisions

### Estrutura dos Relatórios
- **D-01:** Hub centralizado em `/reports` como página única listando todos os relatórios disponíveis. Sem atalhos de exportação nas listas dos módulos por enquanto.
- **D-02:** Quatro relatórios pré-definidos: Equipamentos, Calibrações, Movimentações de Estoque, Dashboard Export. Empréstimos, Aferições e Manutenções ficam para futuras fases.
- **D-03:** Formato tabular simples nos PDFs — tabelas com cabeçalho, linhas de dados, totalizadores no rodapé. Cabeçalho com logo, nome do sistema e data de geração.

### Bibliotecas e Formato
- **D-04:** PDF via `barryvdh/laravel-dompdf` (renderização HTML+CSS → PDF). Sem dependências externas.
- **D-05:** XLSX via `maatwebsite/laravel-excel` (Laravel Excel, baseado PhpSpreadsheet).
- **D-06:** CSV gerado nativamente com `StreamedResponse` + `fputcsv` do Laravel.
- **D-07:** Geração 100% server-side no Laravel. Frontend apenas dispara a requisição e baixa o arquivo.

### Experiência de Download
- **D-08:** SplitButton por relatório: um clique baixa no formato padrão (PDF), dropdown permite selecionar XLSX ou CSV.
- **D-09:** Download direto e síncrono — loading spinner no botão durante a geração, download automático ao final. Sem fila assíncrona.

### Filtros e Parâmetros
- **D-10:** Filtro de período opcional — se não informado, exporta todos os registros. Sempre disponível na sidebar de filtros.
- **D-11:** Filtros específicos limitados a período + status geral (ativo/inativo, pendente/concluído). Sem filtros avançados por módulo.
- **D-12:** Sidebar de filtros (Drawer) para配置 os parâmetros antes de gerar o relatório. Inspirado no painel de filtros do Power BI.

### Convenções Técnicas
- **D-13:** Nomenclatura de arquivos gerados: `{modulo}_{data}_yyyy-mm-dd.{ext}` (ex: `equipamentos_2026-07-27.pdf`).
- **D-14:** Permissões existentes: `relatorios.view` para acesso à página, `relatorios.export` para gerar/download. Já seedadas para perfil `auditor`.
- **D-15:** Rota nomeada `reports.index` já mapeada para módulo `relatorios` na navegação. Módulo frontend em `modules/reports/` (vazio).
</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project & Requirements
- `.planning/ROADMAP.md` — Phase 12: Relatórios (2 plans), REPT-01, REPT-02
- `.planning/REQUIREMENTS.md` §74-78 — REPT-01 (PDF/Excel/CSV), REPT-02 (exportação)
- `.planning/PROJECT.md` — Stack, constraints, key decisions

### Backend Patterns
- `backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php` — Download pattern: `StreamedResponse` + `Storage::disk()->download()`
- `backend/app/Http/Controllers/Controller.php` — Base controller class
- `backend/app/Http/Controllers/Api/V1/EquipmentController.php` — Controller pattern: `HasMiddleware` + `static middleware()`
- `backend/app/Http/Controllers/Api/V1/DashboardController.php` — Service-based controller pattern com caching
- `backend/app/Services/CalibrationCertificateService.php` — Service pattern com file storage
- `backend/routes/api.php` — Route registration conventions
- `backend/database/seeders/RolePermissionSeeder.php` — Permissions `relatorios.view` e `relatorios.export` (linhas 15-16, 132)
- `backend/composer.json` — Dependências atuais (nenhuma lib PDF/Excel instalada)

### Frontend Patterns
- `frontend/src/services/api.ts` — Axios client central
- `frontend/src/types/navigation.ts` — Navigation tree (linha 110: categoria `relatorios`), routeModuleMap (linha 157)
- `frontend/src/router/routes.ts` — Rota `/reports` existente (linha 171) apontando para `PlaceholderPage`
- `frontend/src/views/PlaceholderPage.vue` — Página placeholder atual para `/reports`
- `frontend/src/modules/reports/` — Diretório do módulo (vazio)
- `frontend/src/modules/equipment/types/equipment.ts` — Tipos de Equipment para relatório
- `frontend/src/modules/calibrations/types/calibration.ts` — Tipos de Calibration
- `frontend/src/modules/inventory/types/inventory.ts` — Tipos de InventoryItem
- `frontend/src/modules/dashboard/types/dashboard.ts` — Tipos de DashboardData
</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **Download pattern:** `CalibrationCertificateController::download()` — `StreamedResponse` + `Storage::disk()` é o padrão estabelecido para downloads no backend
- **Controller base:** Todos os controllers estendem `Controller` e implementam `HasMiddleware` com `static middleware()` para auth + permissão
- **Navigation:** Categoria `relatorios` já configurada com ícone `pi pi-file-pdf` e permissão `relatorios.view`
- **Route Module Map:** `reports.index` → `relatorios` já mapeado
- **Types:** Interfaces TypeScript para Equipment, Calibration, Inventory, Dashboard já definidas nos respectivos módulos

### Established Patterns
- **Controller → Service:** Controllers delegam lógica para Services (ex: `EquipmentController` → sem service, `DashboardController` → `DashboardService`)
- **API Resources:** Dados formatados via `Resource` classes (ex: `EquipmentResource`, `CalibrationResource`)
- **Form Requests:** Validação em `StoreXxxRequest` classes
- **Frontend module:** `types/`, `services/`, `pages/` dentro de `modules/{nome}/`
- **Pinia stores:** Composition API (`defineStore('nome', () => {...})`)
- **Permissions:** Middleware `permission:relatorios.view` e `permission:relatorios.export` via array estático

### Integration Points
- **Backend route:** Adicionar rotas em `backend/routes/api.php` dentro do grupo `auth:sanctum` existente
- **Frontend route:** Substituir `PlaceholderPage` na rota `/reports` em `frontend/src/router/routes.ts`
- **Navigation:** Sub-itens podem ser adicionados à categoria `relatorios` em `frontend/src/types/navigation.ts`
</code_context>

<specifics>
## Specific Ideas

- Inspiração visual: painel de filtros similar ao Power BI (sidebar com período + status)
- Nomenclatura de arquivos padronizada: `{modulo}_{data}.{ext}`
- Relatórios do dashboard exportam dados tabulares dos gráficos (não imagens)
</specifics>

<deferred>
## Deferred Ideas

- **Relatório de Empréstimos, Aferições e Manutenções** — fora do escopo da Fase 12, podem entrar em fases futuras de reports adicionais
- **Atalhos de exportação nas listas dos módulos** — botão "Exportar" dentro de cada página de listagem (Equipamentos, Calibrações, Estoque) para exportar o filtro atual
- **Fila assíncrona para relatórios grandes** — geração em background com notificação quando pronto
</deferred>

---

*Phase: 12-Relatórios*
*Context gathered: 2026-07-27*
