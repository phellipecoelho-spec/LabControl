# Requirements: LabControl

**Defined:** 2026-07-19
**Core Value:** Rastreabilidade completa de equipamentos laboratoriais — cada calibração, aferição, movimentação e empréstimo é registrado com auditoria, garantindo conformidade técnica e documental.

## v1 Requirements

### Infraestrutura

- [ ] **INFRA-01**: Docker Compose funcional (build PHP + composer install + containers rodando)
- [ ] **INFRA-02**: Migrations executadas no PostgreSQL
- [ ] **INFRA-03**: Script de setup automatizado funcional

### Autenticação

- [ ] **AUTH-01**: Usuário pode fazer login com email e senha via Sanctum
- [ ] **AUTH-02**: Usuário pode se registrar com verificação de email
- [ ] **AUTH-03**: Usuário pode recuperar senha via email
- [ ] **AUTH-04**: Sessão do usuário persiste entre atualizações (refresh token)

### Usuários e Permissões

- [ ] **USERS-01**: Administrador pode gerenciar usuários (CRUD) com perfis (Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor)
- [ ] **USERS-02**: Administrador pode atribuir permissões por papel
- [ ] **USERS-03**: Usuário pode editar próprio perfil com avatar

### Layout e Navegação

- [ ] **LAYOUT-01**: Tema escuro responsivo com design moderno
- [ ] **LAYOUT-02**: Sidebar com navegação por módulos
- [ ] **LAYOUT-03**: Topbar com notificações e menu do usuário

### Equipamentos

- [ ] **EQUIP-01**: Usuário pode cadastrar equipamentos com dados completos
- [ ] **EQUIP-02**: Usuário pode gerenciar categorias, fabricantes e fornecedores
- [ ] **EQUIP-03**: Usuário pode anexar fotos e manuais ao equipamento
- [ ] **EQUIP-04**: Sistema registra histórico de alterações do equipamento

### Estoque

- [ ] **INVT-01**: Usuário pode controlar estoque de insumos e peças
- [ ] **INVT-02**: Usuário pode registrar movimentações de entrada e saída
- [ ] **INVT-03**: Sistema alerta quando estoque atinge mínimo

### Empréstimos

- [ ] **LOAN-01**: Usuário pode registrar empréstimos de equipamentos
- [ ] **LOAN-02**: Usuário pode visualizar agenda de reservas
- [ ] **LOAN-03**: Sistema notifica quando devolução está atrasada

### Calibrações

- [ ] **CAL-01**: Usuário pode gerenciar agenda de calibrações periódicas
- [ ] **CAL-02**: Usuário pode anexar certificados de calibração
- [ ] **CAL-03**: Sistema alerta quando calibração está vencida
- [ ] **CAL-04**: Usuário pode consultar histórico de calibrações por equipamento

### Aferições

- [ ] **VERF-01**: Usuário pode registrar aferições diárias
- [ ] **VERF-02**: Sistema alerta quando limites de tolerância são excedidos

### Manutenções

- [ ] **MAINT-01**: Usuário pode abrir ordens de manutenção
- [ ] **MAINT-02**: Sistema mantém histórico de manutenções preventivas e corretivas

### Dashboard

- [ ] **DASH-01**: Usuário visualiza dashboard com indicadores (ECharts)
- [ ] **DASH-02**: Dashboard exibe gráficos de equipamentos, calibrações e movimentações

### Relatórios

- [ ] **REPT-01**: Usuário pode gerar relatórios em PDF, Excel e CSV
- [ ] **REPT-02**: Usuário pode exportar dados do sistema

### PWA e Offline

- [ ] **PWA-01**: Sistema funciona offline com sincronização automática
- [ ] **PWA-02**: Sistema é instalável como aplicativo

### Auditoria

- [ ] **LOGS-01**: Sistema audita todas as operações
- [ ] **LOGS-02**: Usuário pode visualizar logs por módulo

## v2 Requirements (Deferidas)

- **MOBL-01**: Aplicativo mobile via Capacitor
- **IOT-01**: Integração com equipamentos via IoT
- **MULTI-01**: Suporte multiempresa e multilaboratório
- **ENSAIO-01**: Controle de ensaios e protocolos
- **PROD-01**: Controle de produtividade da equipe

## Out of Scope

| Feature | Reason |
|---------|--------|
| Aplicativo mobile nativo | PWA suficiente para v1, Capacitor no futuro |
| Chat interno | Usar ferramentas externas |
| Videoconferência | Usar ferramentas externas |
| Integração IoT com equipamentos | v2+ |
| Faturamento/NFe | Não faz parte de gestão laboratorial |
| CRM | Fora do escopo |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| INFRA-01 | Phase 1 | Pending |
| INFRA-02 | Phase 1 | Pending |
| INFRA-03 | Phase 1 | Pending |
| AUTH-01 | Phase 2 | Pending |
| AUTH-02 | Phase 2 | Pending |
| AUTH-03 | Phase 2 | Pending |
| AUTH-04 | Phase 2 | Pending |
| USERS-01 | Phase 3 | Pending |
| USERS-02 | Phase 3 | Pending |
| USERS-03 | Phase 3 | Pending |
| LAYOUT-01 | Phase 4 | Pending |
| LAYOUT-02 | Phase 4 | Pending |
| LAYOUT-03 | Phase 4 | Pending |
| EQUIP-01 | Phase 5 | Pending |
| EQUIP-02 | Phase 5 | Pending |
| EQUIP-03 | Phase 5 | Pending |
| EQUIP-04 | Phase 5 | Pending |
| INVT-01 | Phase 6 | Pending |
| INVT-02 | Phase 6 | Pending |
| INVT-03 | Phase 6 | Pending |
| LOAN-01 | Phase 7 | Pending |
| LOAN-02 | Phase 7 | Pending |
| LOAN-03 | Phase 7 | Pending |
| CAL-01 | Phase 8 | Pending |
| CAL-02 | Phase 8 | Pending |
| CAL-03 | Phase 8 | Pending |
| CAL-04 | Phase 8 | Pending |
| VERF-01 | Phase 9 | Pending |
| VERF-02 | Phase 9 | Pending |
| MAINT-01 | Phase 10 | Pending |
| MAINT-02 | Phase 10 | Pending |
| DASH-01 | Phase 11 | Pending |
| DASH-02 | Phase 11 | Pending |
| REPT-01 | Phase 12 | Pending |
| REPT-02 | Phase 12 | Pending |
| PWA-01 | Phase 13 | Pending |
| PWA-02 | Phase 13 | Pending |
| LOGS-01 | Phase 3 | Pending |
| LOGS-02 | Phase 3 | Pending |

**Coverage:**
- v1 requirements: 38 total
- Mapped to phases: 38
- Unmapped: 0 ✓

---
*Requirements defined: 2026-07-19*
*Last updated: 2026-07-19 after initial definition*
