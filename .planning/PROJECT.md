# LabControl

## Current Milestone: v1.1 Verificação, Revisão e Ajustes de Layout

**Goal:** Garantir funcionamento adequado da aplicação aplicando todas as verificações/revisões pendentes de v1.0 e revisar/ajustar o layout da interface.

**Target features:**
- UAT pendente de Aferições (Fase 09) — 6 cenários
- UAT pendente de Manutenções (Fase 10) — 5 itens visuais
- E2E tests com Playwright
- ForgotPasswordSentView dedicada
- Correções de bugs de funcionamento (ex.: seeders em ambiente limpo)
- Revisão e ajuste de layout/UI

## What This Is

Plataforma modular de gestão laboratorial (ERP) para controle patrimonial, estoque, metrologia, calibrações, aferições, empréstimos, controle documental, dashboards e relatórios. Sistema multiusuário com autenticação, controle de permissões por perfis, PWA com sincronização offline, preparado para múltiplos laboratórios e multiempresa.

## Core Value

Rastreabilidade completa de equipamentos laboratoriais — cada calibração, aferição, movimentação e empréstimo é registrado com auditoria, garantindo conformidade técnica e documental.

## Requirements

### Validated

- ✓ INFRA-01: Docker Compose funcional — v1.0
- ✓ INFRA-02: Migrations executadas no PostgreSQL — v1.0
- ✓ INFRA-03: Script de setup automatizado funcional — v1.0
- ✓ AUTH-01: Login com email e senha via Sanctum — v1.0
- ✓ AUTH-02: Registro de usuário com verificação de email — v1.0
- ✓ AUTH-03: Recuperação de senha — v1.0
- ✓ AUTH-04: Sessão persistente com refresh token — v1.0
- ✓ USERS-01: CRUD de usuários com perfis — v1.0
- ✓ USERS-02: Atribuição de permissões por papel — v1.0
- ✓ USERS-03: Perfil de usuário com avatar — v1.0
- ✓ LAYOUT-01: Tema escuro responsivo — v1.0
- ✓ LAYOUT-02: Sidebar com navegação por módulos — v1.0
- ✓ LAYOUT-03: Topbar com notificações e menu do usuário — v1.0
- ✓ EQUIP-01: Cadastro completo de equipamentos — v1.0
- ✓ EQUIP-02: Categorias, fabricantes, fornecedores — v1.0
- ✓ EQUIP-03: Ficha técnica com anexos (fotos) — v1.0
- ✓ EQUIP-04: Histórico de alterações — v1.0
- ✓ INVT-01: Controle de estoque de insumos e peças — v1.0
- ✓ INVT-02: Movimentações de entrada e saída — v1.0
- ✓ LOAN-01: Controle de empréstimos de equipamentos — v1.0
- ✓ LOAN-02: Agenda de reservas — v1.0
- ✓ LOAN-03: Notificações de devolução — v1.0
- ✓ CAL-01: Agenda de calibrações periódicas — v1.0
- ✓ CAL-02: Certificados de calibração (upload) — v1.0
- ✓ CAL-03: Alertas de calibração vencida — v1.0
- ✓ CAL-04: Histórico de calibrações por equipamento — v1.0
- ✓ VERF-01: Registro de aferições diárias — v1.0
- ✓ VERF-02: Limites de tolerância e alertas — v1.0
- ✓ MAINT-01: Ordens de manutenção — v1.0
- ✓ MAINT-02: Histórico de manutenções — v1.0
- ✓ DASH-01: Dashboard com indicadores (ECharts) — v1.0
- ✓ DASH-02: Gráficos de equipamentos, calibrações, movimentações — v1.0
- ✓ REPT-01: Relatórios em PDF, Excel, CSV — v1.0
- ✓ REPT-02: Exportação de dados — v1.0
- ✓ PWA-01: Funcionamento offline com sincronização — v1.0
- ✓ PWA-02: Instalável como aplicativo — v1.0
- ✓ LOGS-01: Auditoria de todas as operações — v1.0
- ✓ LOGS-02: Visualização de logs por módulo/usuario — v1.0

### Active

- [ ] UAT-01: 6 cenários UAT de Aferições (Fase 09) verificados — v1.1
- [ ] UAT-02: 5 itens visuais UAT de Manutenções (Fase 10) verificados — v1.1
- [ ] E2E tests (Playwright) cobrindo fluxos críticos — v1.1
- [ ] ForgotPasswordSentView dedicada — v1.1
- [ ] BUG-01: Seeders funcionam em ambiente limpo (login admin pós-setup) — v1.1
- [ ] LAYOUT-01: Revisão e ajuste de layout/UI — v1.1
- [ ] PWA-03: UI indicators (offline banner, sync status chip) — v1.1 (deferido, se couber)
- [ ] INVT-03: Alertas de estoque mínimo — v1.1 (deferido, se couber)

### Out of Scope

- Aplicativo mobile nativo — PWA suficiente para v1, Capacitor no futuro
- Chat interno — usar ferramentas externas
- Videoconferência — usar ferramentas externas
- Integração com equipamentos via IoT — v2+
- Faturamento/NFe — não faz parte do escopo de gestão laboratorial
- CRM — fora do escopo

## Context

**v1.0 shipped 2026-07-28.** Sistema completo de gestão laboratorial com 14 fases, 42 planos, 510+ arquivos, ~84k linhas de código.

Projeto originado de uma planilha Excel com VBA, migrado para stack web profissional. A stack foi escolhida para suportar crescimento de planilha simples (~2k linhas VBA) para plataforma empresarial (~20k+ linhas). Público-alvo: laboratórios de metrologia, calibração e ensaios que precisam de rastreabilidade documental completa.

Deferred items: INVT-03 (stock alerts), PWA plan 03 (UI indicators), E2E tests, ForgotPasswordSentView UX, Phase 09/10 UAT verification. All non-blocking for v1.0.

## Constraints

- **Stack**: Vue 3 + PrimeVue + Laravel + PostgreSQL + Docker — decisão arquitetural já tomada
- **Licenciamento**: 100% open source, sem dependências pagas
- **Hospedagem**: Local (Docker) e cloud (VPS)
- **Offline**: Suporte offline obrigatório (PWA)
- **Multi-usuário**: Suporte desde a primeira versão

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Vue 3 + PrimeVue (vs React/Angular) | Curva de aprendizado, ecossistema, PWA nativo | ✓ Good |
| Laravel (vs NestJS) | Experiência do dev com PHP, ecossistema maduro | ✓ Good |
| PostgreSQL (vs MySQL/SQLite) | Robusto, JSON, GIS, milhares de usuários | ✓ Good |
| Docker Compose | Mesma stack local e produção | ✓ Good |
| UUIDs (vs auto-increment) | Segurança, distributed-friendly | ✓ Good |
| Sanctum (vs JWT) | Simplicidade, SPA-first | ✓ Good |
| Módulos independentes | Permite comercialização futura por módulo | ✓ Good |
| PWA (vs app nativo) | Custo zero, entrega contínua | ✓ Good |
| LogsActivity trait (vs observers) | Reutilizável, bootable, sem boilerplate | ✓ Good |
| EmptyState + LoadingSkeleton | Componentes reutilizáveis, padrão visual único | ✓ Good |
| Rate limiting 5 req/min login | Proteção brute force básica | ✓ Good |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---

*Last updated: 2026-08-09 after starting milestone v1.1*
