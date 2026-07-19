# LabControl

## What This Is

Plataforma modular de gestão laboratorial (ERP) para controle patrimonial, estoque, metrologia, calibrações, aferições, empréstimos, controle documental, dashboards e relatórios. Sistema multiusuário com autenticação, controle de permissões por perfis, PWA com sincronização offline, preparado para múltiplos laboratórios e multiempresa.

## Core Value

Rastreabilidade completa de equipamentos laboratoriais — cada calibração, aferição, movimentação e empréstimo é registrado com auditoria, garantindo conformidade técnica e documental.

## Requirements

### Validated

(Estrutura inicial — Sprint 0 — fundação do projeto)

- ✓ Arquitetura definida (CONTEXT.md + PLANNER.md) — Sprint 0
- ✓ Estrutura de diretórios completa (frontend, backend, docker, docs, scripts) — Sprint 0
- ✓ Backend Laravel 13.20 com migrations iniciais (users UUID, roles, permissions, logs) — Sprint 0
- ✓ Frontend Vue 3 + Vite 8 + TypeScript + PrimeVue + Pinia — Sprint 0
- ✓ Docker Compose (nginx, php-fpm, postgres 17, redis 7) — Sprint 0
- ✓ Migrações iniciais do banco — Sprint 0

### Active

- **INFRA-01**: Docker Compose funcional (build PHP + composer install + containers rodando)
- **INFRA-02**: Migrations executadas no PostgreSQL
- **INFRA-03**: Script de setup automatizado funcional
- **AUTH-01**: Login com email e senha via Sanctum
- **AUTH-02**: Registro de usuário com verificação de email
- **AUTH-03**: Recuperação de senha
- **AUTH-04**: Sessão persistente com refresh token
- **USERS-01**: CRUD de usuários com perfis (Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor)
- **USERS-02**: Atribuição de permissões por papel
- **USERS-03**: Perfil de usuário com avatar e dados pessoais
- **LAYOUT-01**: Tema escuro responsivo (inspirado Power BI / Linear / Notion)
- **LAYOUT-02**: Sidebar com navegação por módulos
- **LAYOUT-03**: Topbar com notificações e menu do usuário
- **EQUIP-01**: Cadastro completo de equipamentos
- **EQUIP-02**: Categorias, fabricantes, fornecedores
- **EQUIP-03**: Ficha técnica com anexos (fotos, manuais)
- **EQUIP-04**: Histórico de alterações
- **INVT-01**: Controle de estoque de insumos e peças
- **INVT-02**: Movimentações de entrada e saída
- **INVT-03**: Alertas de estoque mínimo
- **LOAN-01**: Controle de empréstimos de equipamentos
- **LOAN-02**: Agenda de reservas
- **LOAN-03**: Notificações de devolução
- **CAL-01**: Agenda de calibrações periódicas
- **CAL-02**: Certificados de calibração (upload e armazenamento)
- **CAL-03**: Alertas de calibração vencida
- **CAL-04**: Histórico de calibrações por equipamento
- **VERF-01**: Registro de aferições diárias
- **VERF-02**: Limites de tolerância e alertas
- **MAINT-01**: Ordens de manutenção
- **MAINT-02**: Histórico de manutenções preventivas e corretivas
- **DASH-01**: Dashboard com indicadores (ECharts)
- **DASH-02**: Gráficos de equipamentos, calibrações, movimentações
- **REPT-01**: Relatórios em PDF, Excel, CSV
- **REPT-02**: Exportação de dados
- **PWA-01**: Funcionamento offline com sincronização
- **PWA-02**: Instalável como aplicativo
- **LOGS-01**: Auditoria de todas as operações
- **LOGS-02**: Visualização de logs por módulo/usuario

### Out of Scope

- Aplicativo mobile nativo — PWA suficiente para v1, Capacitor no futuro
- Chat interno — usar ferramentas externas
- Videoconferência — usar ferramentas externas
- Integração com equipamentos via IoT — v2+
- Faturamento/NFe — não faz parte do escopo de gestão laboratorial
- CRM — fora do escopo

## Context

Projeto originado de uma planilha Excel com VBA, migrado para stack web profissional. A stack foi escolhida para suportar crescimento de planilha simples (~2k linhas VBA) para plataforma empresarial (~20k+ linhas). Público-alvo: laboratórios de metrologia, calibração e ensaios que precisam de rastreabilidade documental completa.

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
| Sanctum (vs JWT) | Simplicidade, SPA-first | — Pending |
| Módulos independentes | Permite comercialização futura por módulo | ✓ Good |
| PWA (vs app nativo) | Custo zero, entrega contínua | ✓ Good |

---

*Last updated: 2026-07-19 after project initialization*
