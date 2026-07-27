---
phase: 11-dashboard
plan: 01
subsystem: backend
tags: [laravel, dashboard, kpi, redis, cache, aggregation, phpunit]
requires:
  - phase: 05-equipamentos
    provides: Equipment, Category models
  - phase: 06-estoque
    provides: InventoryMovement model
  - phase: 07-emprestimos
    provides: Loan model
  - phase: 08-calibracoes
    provides: Calibration model
  - phase: 09-afericoes
    provides: VerificationService
  - phase: 10-manutencoes
    provides: MaintenanceOrder model
provides:
  - DashboardService com agregação de KPIs e cache Redis (TTL 300s)
  - DashboardController single-action (__invoke) com middleware auth:sanctum + permission:dashboard.view
  - Rota GET /api/v1/dashboard com filtro de período (start_date/end_date)
  - Feature tests (4 cenários) e unit tests (3 cenários)
affects: [frontend-dashboard, 11-dashboard-plan02]

tech-stack:
  added: []
  patterns:
    - Cache::remember com TTL para agregações globais (não por usuário)
    - Controller single-action (__invoke) com injeção de dependência via type-hint
    - Queries agregadas com selectRaw + CASE WHEN + GROUP BY (sem N+1)
    - Delegação para VerificationService via app() helper (sem acoplamento forte)

key-files:
  created:
    - backend/app/Services/DashboardService.php
    - backend/app/Http/Controllers/Api/V1/DashboardController.php
    - backend/tests/Feature/DashboardControllerTest.php
    - backend/tests/Unit/Services/DashboardServiceTest.php
  modified:
    - backend/routes/api.php

key-decisions:
  - "Cache global (não por usuário) com TTL de 5 minutos conforme D-13"
  - "DashboardController usa __invoke com type-hint para auto-resolve do DashboardService"
  - "pending_verifications_today delega para VerificationService::getPendingVerifications() evitando duplicar lógica"
  - "VerificationService chamado via app() helper (não DI no construtor) seguindo padrão do projeto"
  - "CalibrationsTimeline usa next_due_at BETWEEN start/end com CASE WHEN para status scheduled/completed/due"

patterns-established:
  - "Service de agregação: método público único aggregate() com corpo envolvido em Cache::remember"
  - "Métodos privados por domínio (kpis, equipmentsByCategory, calibrationsTimeline, stockMovements)"
  - "Queries 100% agregadas (count, withCount, selectRaw) — sem N+1 e sem loops PHP"

requirements-completed: [DASH-01, DASH-02]

coverage:
  - id: D1
    description: DashboardService com 5 KPIs agregados, cache Redis TTL 300s e 3 métodos de gráfico com selectRaw + groupBy mensal
    requirement: DASH-01
    verification:
      - kind: unit
        ref: backend/tests/Unit/Services/DashboardServiceTest.php#test_kpis_return_correct_values
        status: pass
      - kind: unit
        ref: backend/tests/Unit/Services/DashboardServiceTest.php#test_equipments_by_category_returns_expected_structure
        status: pass
      - kind: unit
        ref: backend/tests/Unit/Services/DashboardServiceTest.php#test_cache_is_used_on_subsequent_calls
        status: pass
    human_judgment: false
  - id: D2
    description: DashboardController com middleware auth:sanctum + permission:dashboard.view, rota GET /api/v1/dashboard com filtro de período
    requirement: DASH-02
    verification:
      - kind: unit
        ref: backend/tests/Feature/DashboardControllerTest.php#test_unauthenticated_user_cannot_access_dashboard
        status: pass
      - kind: unit
        ref: backend/tests/Feature/DashboardControllerTest.php#test_dashboard_returns_kpis_and_charts_structure
        status: pass
      - kind: unit
        ref: backend/tests/Feature/DashboardControllerTest.php#test_dashboard_respects_date_filter
        status: pass
      - kind: unit
        ref: backend/tests/Feature/DashboardControllerTest.php#test_kpis_contain_numeric_values
        status: pass
    human_judgment: false

duration: 2min
completed: 2026-07-27
status: complete
---

# Phase 11: Dashboard — Plan 01 — Backend Summary

**DashboardService com cache Redis, DashboardController single-action, rota API e 7 testes automatizados**

## Performance

- **Duration:** 2 min
- **Started:** 2026-07-27T16:14:13-03:00
- **Completed:** 2026-07-27T16:15:03-03:00
- **Tasks:** 3
- **Files modified:** 5

## Accomplishments

- **DashboardService.aggregate()** com `Cache::remember('dashboard', 300)` usando Redis — cache global de 5 minutos
- **5 KPIs** calculados com queries agregadas: total_equipments, calibrations_due_soon (30d), active_loans, pending_verifications_today (delega para VerificationService), open_maintenance_orders
- **3 métodos de gráfico**: equipmentsByCategory (Category::withCount), calibrationsTimeline (selectRaw com CASE WHEN para scheduled/completed/due), stockMovements (selectRaw incoming/outgoing por mês)
- **DashboardController** single-action (`__invoke`) com `auth:sanctum` + `permission:dashboard.view` middleware, validação de datas via `$request->date()` com defaults (12 meses atrás / hoje)
- **Rota `GET /api/v1/dashboard`** registrada dentro do grupo auth:sanctum, posicionada como primeira rota do grupo autenticado
- **Feature tests** (4 cenários): unauthenticated (401), structure assertion (kpis + charts), date filter, numeric KPI values
- **Unit tests** (3 cenários): KPI correct values, chart structure, cache behavior via Cache::spy

## Task Commits

Each task was committed atomically:

1. **Task 1: Criar DashboardService com agregações e cache Redis** - `fb14dd3` (feat)
2. **Task 2: Criar DashboardController e registrar rota API** - `891bc06` (feat)
3. **Task 3: Criar testes de feature e unit para o Dashboard** - `daef6cf` (test)

## Files Created/Modified

- `backend/app/Services/DashboardService.php` - Serviço de agregação com cache Redis, 5 KPIs e 3 métodos de gráfico
- `backend/app/Http/Controllers/Api/V1/DashboardController.php` - Controller single-action com middleware e validação de datas
- `backend/routes/api.php` - Import do controller + rota GET /api/v1/dashboard adicionada
- `backend/tests/Feature/DashboardControllerTest.php` - 4 testes de feature (unauthenticated, structure, date filter, numeric)
- `backend/tests/Unit/Services/DashboardServiceTest.php` - 3 testes unitários (kpis, chart structure, cache)

## Decisions Made

- **Cache global (não por usuário)** — KPIs são globais, TTL de 5 minutos evita reprocessamento (D-13)
- **Controller single-action `__invoke`** — padrão mais limpo para endpoint único, Laravel auto-resolve DashboardService via type-hint
- **Delegação via `app()` helper** — `VerificationService` é chamado via `app(VerificationService::class)` dentro do método, não via DI no construtor, seguindo padrão do código existente e evitando acoplamento forte
- **Queries 100% agregadas** — `selectRaw` com `CASE WHEN` e `GROUP BY` no SQL, sem N+1, sem loops PHP
- **Rota posicionada como primeira do grupo auth:sanctum** — Dashboard é a rota principal da SPA

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Backend do Dashboard completo com cache Redis e testes
- Pronto para o Plano 02 (Frontend): componentes ECharts, DashboardPage, KpiRow, KpiCard e seletor de período

## Self-Check: PASSED

---

*Phase: 11-dashboard*
*Completed: 2026-07-27*
