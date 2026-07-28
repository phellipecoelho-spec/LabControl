# LabControl

Sistema de Gestão Laboratorial — controle de equipamentos, calibrações, aferições, empréstimos, e manutenções com auditoria completa.

## Stack

| Layer | Tecnologia |
|-------|-----------|
| Frontend | Vue 3 + Vite + TypeScript + PrimeVue 5 + Pinia |
| Backend | Laravel 13 (API REST) + Sanctum |
| Database | PostgreSQL 16 |
| Cache/Queue | Redis 7 |
| Container | Docker Compose (nginx + php-fpm + postgres + redis) |
| Auth | Sanctum SPA (session cookies) |
| Charts | Apache ECharts |
| Reports | PDF (DomPDF) + Excel (Laravel Excel) |

## Pré-requisitos

- Docker + Docker Compose
- Git

## Setup rápido

```bash
git clone <repo-url> labcontrol
cd labcontrol

# Linux/Mac
chmod +x scripts/setup.sh
./scripts/setup.sh

# Windows (PowerShell)
.\scripts\setup.ps1
```

Acessar: http://localhost

### Credenciais padrão

| Papel | Email | Senha |
|-------|-------|-------|
| Admin | admin@labcontrol.com | @dmin123 |

## Estrutura do Projeto

```
labcontrol/
├── backend/          # Laravel 13 API
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/  # 20 controllers
│   │   ├── Models/                    # 16 modelos
│   │   ├── Services/                  # Services layer
│   │   ├── Traits/                    # Reusable traits
│   │   └── Enums/                     # Status enums
│   ├── database/
│   │   ├── migrations/                # Schema definitions
│   │   └── seeders/                   # Initial data
│   └── tests/Feature/                 # 25+ feature tests
├── frontend/         # Vue 3 SPA
│   └── src/
│       ├── modules/                   # Feature modules
│       ├── components/                # Shared components
│       ├── stores/                    # Pinia stores
│       └── services/                  # API services
├── docker/           # Docker Compose + configs
├── scripts/          # Backup + setup scripts
└── docs/             # Documentation
```

## Funcionalidades

- **Equipamentos**: Cadastro completo com fotos, categorias, fabricantes
- **Estoque**: Controle de insumos e peças com movimentações
- **Empréstimos**: Reserva, ativação, devolução com controle de atrasos
- **Calibrações**: Agenda periódica, certificados, alertas de vencimento
- **Aferições**: Verificações diárias com controle de tolerância
- **Manutenções**: Ordens preventivas e corretivas com histórico completo
- **Dashboard**: Indicadores e gráficos (ECharts)
- **Relatórios**: PDF, Excel, CSV
- **Auditoria**: Logs detalhados de todas as operações
- **PWA**: Funciona offline com sincronização automática

## Licença

Privado — uso interno.