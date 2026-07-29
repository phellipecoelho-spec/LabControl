# Phase 14: Auditoria e Ajustes Finais — Context

**Gathered:** 2026-07-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Última fase antes do v1.0 Production. Engloba verificação de auditoria cross-cutting, polimento de UI, correção de bugs pendentes de fases anteriores, preparação para deploy, documentação do projeto, e atualização da tabela de rastreabilidade de requisitos.

**Includes:**
- Verificação de cobertura de auditoria (LogsActivity trait) em todos os módulos
- Polimento de UI (empty states, loading skeletons, responsividade, consistência visual)
- Correção de bugs e gaps de UAT/verificação de fases anteriores (02, 09, 10)
- Preparação para deploy (Docker, env, scripts de backup)
- Documentação do projeto (API, README, deployment guide, architecture docs)
- Atualização da tabela de rastreabilidade em REQUIREMENTS.md

**Não inclui:**
- Novas funcionalidades fora do escopo v1.0
- Migração para Capacitor (MOBL-01 — v2)
- Multiempresa (MULTI-01 — v2)
</domain>

<decisions>
## Implementation Decisions

### 1. Sequenciamento
- **D-01:** Execução sequencial: Audit → Polish → Fixes → Deploy → Docs
- **D-02:** Cada área é um plano separado (~2-3 tasks cada)

### 2. Auditoria Cross-Cutting
- **D-03:** Verificar que todos os controllers/actions dos módulos (Equipment, Inventory, Loans, Calibrations, Verifications, Maintenance) chamam LogsActivity trait ou audit logging
- **D-04:** Criar testes de feature que verificam a existência de ActivityLog entries após operações CRUD em cada módulo
- **D-05:** Backend audit coverage é prioridade máxima (Plano 01)

### 3. Polimento de UI
- **D-06:** Verificar todos os módulos quanto a: empty states, loading skeletons, responsividade mobile, dark mode consistency, error handling visual
- **D-07:** Usar componentes PrimeVue existentes (Skeleton, Message, InlineMessage) — não criar novos componentes de propósito geral
- **D-08:** Padronizar empty states com ilustração + texto + CTAaction

### 4. Correção de Bugs
- **D-09:** Priorizar UAT gaps com status "diagnosed" ou "partial" (Phase 09 Aferições, Phase 10 Manutenções)
- **D-10:** Priorizar verification gaps com status "gaps_found" ou "human_needed" (Phase 02 Autenticação)
- **D-11:** Cada bug fix deve ter test associado

### 5. Deploy
- **D-12:** Docker Compose final com health checks, volumes, networks configurados
- **D-13:** Script de backup do PostgreSQL + upload para storage
- **D-14:** Script de setup automatizado (setup.sh / setup.ps1) para novo deploy
- **D-15:** Documentação de .env com todas as variáveis necessárias

### 6. Documentação
- **D-16:** README.md na raiz com visão geral, stack, pré-requisitos, instruções de setup
- **D-17:** Guia de deploy (docker-compose + nginx + SSL)
- **D-18:** Arquitetura do sistema (diagrama, fluxos, decisões) em docs/ARCHITECTURE.md
- **D-19:** API documentada via OpenAPI/Swagger (Laravel + l5-swagger ou scribe)

### 7. Rastreabilidade
- **D-20:** Atualizar REQUIREMENTS.md traceability table com status real de cada requisito
- **D-21:** Adicionar coluna "Module" e "Verified by" na tabela

</decisions>

<canonical_refs>
## Canonical References

### Requirements
- `.planning/REQUIREMENTS.md` — Traceability table (to be updated in Plan 01)

### Prior Audit Gaps
- `.planning/phases/09-afericoes/09-UAT.md` — Pending UAT items
- `.planning/phases/09-afericoes/09-VERIFICATION.md` — Verification gaps
- `.planning/phases/10-manutencaoes/10-VERIFICATION.md` — Verification gaps
- `.planning/phases/02-autenticacao/02-VERIFICATION.md` — Gaps (rate limit, forgot password view)
- `.planning/phases/07-emprestimos/07-VERIFICATION.md` — Verification items

### Existing Audit Infrastructure
- `backend/app/Traits/LogsActivity.php` — Activity logging trait
- `backend/app/Models/ActivityLog.php` — Audit log model
- `frontend/src/modules/admin/pages/AuditLogsPage.vue` — Audit viewer

### Codebase Patterns
- `.planning/codebase/STRUCTURE.md` — Frontend and backend structure
- `.planning/codebase/CONCERNS.md` — Known codebase quality concerns
</canonical_refs>

<deferred>
## Deferred for Later

- Testes E2E com Playwright (cobertura completa) — pós v1.0
- CI/CD pipeline (GitHub Actions) — pós v1.0
- Monitoramento e observabilidade — pós v1.0

</deferred>

---

*Phase: 14-Auditoria-e-Ajustes-Finais*
*Context gathered: 2026-07-28*
