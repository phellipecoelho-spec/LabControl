# Requirements: LabControl

**Defined:** 2026-08-09
**Core Value:** Rastreabilidade completa de equipamentos laboratoriais — cada calibração, aferição, movimentação e empréstimo é registrado com auditoria, garantindo conformidade técnica e documental.

## v1.1 Requirements

Requisitos do milestone v1.1 — Verificação, Revisão e Ajustes de Layout. Todos derivam de débitos do v1.0 (registrados em STATE.md Deferred Items e PROJECT.md Active).

### Verificação UAT

- [ ] **UAT-01**: Usuário pode executar os 6 cenários UAT de Aferições (Fase 09) sem falhas ou bloqueios
- [ ] **UAT-02**: Usuário pode executar os 5 itens visuais UAT de Manutenções (Fase 10) sem falhas ou bloqueios

### Testes E2E

- [ ] **E2E-01**: Projeto Playwright configurado com base instalada e script de execução documentado
- [ ] **E2E-02**: Fluxos de autenticação cobertos por testes E2E (login, logout, recuperação de senha)
- [ ] **E2E-03**: Fluxos críticos de negócio cobertos por testes E2E (equipamentos, estoque, empréstimos)

### Autenticação UX

- [ ] **AUTH-05**: Usuário vê tela dedicada de confirmação após solicitar recuperação de senha (ForgotPasswordSentView)
- [ ] **AUTH-06**: Usuário recebe feedback claro ao verificar email (sucesso e erro)

### Correções de Funcionamento

- [ ] **BUG-01**: Usuário admin consegue logar em ambiente limpo (seeders criam admin/roles/permissões após setup)
- [ ] **BUG-02**: Bugs encontrados na varredura de verificação são corrigidos e validados

### Revisão de Layout

- [ ] **LAYOUT-01**: Interface revisada com consistência de tema escuro em todas as telas
- [ ] **LAYOUT-02**: Interface responsiva verificada em breakpoints de tela (desktop, tablet, mobile)

### PWA

- [ ] **PWA-03**: Usuário vê indicadores visuais de offline e status de sincronização na UI

### Estoque

- [ ] **INVT-03**: Usuário recebe alertas de estoque mínimo (item abaixo do limite)

## v2 Requirements

Deferidos para release futuro. Não estão no roadmap atual.

### Multiempresa / Multilaboratório

- **TENANT-01**: Sistema suporta múltiplos laboratórios na mesma instalação
- **TENANT-02**: Usuário é isolado por laboratório/empresa

### Integração com Equipamentos

- **IOT-01**: Integração com equipamentos via IoT para leitura automatizada

## Out of Scope

| Feature | Reason |
|---------|--------|
| Aplicativo mobile nativo | PWA suficiente para v1.x; Capacitor no futuro |
| Chat interno | Usar ferramentas externas |
| Videoconferência | Usar ferramentas externas |
| Faturamento/NFe | Não faz parte do escopo de gestão laboratorial |
| CRM | Fora do escopo |
| Migração de dados legados | Sem fonte legada definida |
| Testes de carga/perfomance em produção | Adequado a fase posterior de hardening |

## Traceability

Preenchido durante a criação do roadmap.

| Requirement | Phase | Status |
|-------------|-------|--------|
| UAT-01 | Phase 16 | Pending |
| UAT-02 | Phase 16 | Pending |
| E2E-01 | Phase 18 | Pending |
| E2E-02 | Phase 18 | Pending |
| E2E-03 | Phase 18 | Pending |
| AUTH-05 | Phase 17 | Pending |
| AUTH-06 | Phase 17 | Pending |
| BUG-01 | Phase 15 | Pending |
| BUG-02 | Phase 15 | Pending |
| LAYOUT-01 | Phase 19 | Pending |
| LAYOUT-02 | Phase 19 | Pending |
| PWA-03 | Phase 20 | Pending |
| INVT-03 | Phase 20 | Pending |

**Coverage:**
- v1.1 requirements: 13 total
- Mapped to phases: 13
- Unmapped: 0 ✓

---
*Requirements defined: 2026-08-09*
*Last updated: 2026-08-09 after v1.1 roadmap creation (Phases 15-20)*
