# LabControl — Architecture

## System Overview

```
Browser (PWA)
     │
     ▼
Nginx (reverse proxy)
     │
┌─────▼──────┐
│  Laravel   │
│  API REST  │
└─────┬──────┘
     │
┌─────▼──────┐   ┌────────┐
│ PostgreSQL │──▶│  Redis │
└────────────┘   └────────┘
```

## Backend Architecture

### Layers

- **Controllers**: Validação (Form Request) + delegação ao Service
- **Services**: Lógica de negócio + transações + notificações
- **Models**: Eloquent ORM + LogsActivity trait + SoftDeletes
- **Resources**: Transformação de dados para API JSON
- **Enums**: Status e tipos tipados (PHP 8 enums)
- **Form Requests**: Validação e autorização por ação

### Authentication

- Sanctum SPA: session-based cookies (não tokens Bearer)
- Middleware `auth:sanctum` em todas as rotas protegidas
- Login via POST /api/v1/auth/login → session cookie
- Rate limit: 5 tentativas/minuto por IP

### Authorization

- Role-based: Admin, Supervisor, Laboratorista, Técnico, Consulta, Auditor
- Permission-based via middleware `permission:{module}.{action}`
- Roles x permissions via tabela pivô

### Audit

- LogsActivity trait: boot() → created/updated/deleted → ActivityLog
- AuthController: login, login_failed, logout registrados manualmente
- ActivityLog: user_id, action, module, table_name, record_id, old/new_values, ip

### Notifications

- Email: MaintenanceOrderCreated, LoanOverdue, CalibrationDue, ToleranceExceeded
- Queue: Redis driver para processamento assíncrono
- Database notification table para notificações in-app

## Frontend Architecture

### Module Structure

Cada módulo segue:

```
modules/{feature}/
├── pages/         # Route-level components
├── components/    # Feature-specific components
├── services/      # API client methods
├── stores/        # Pinia state management
└── types/         # TypeScript interfaces
```

### State Management

- Pinia stores com actions (async API calls) + state (loading, error, data)
- Composables para lógica reutilizável
- Services para comunicação HTTP via Axios

### Offline (PWA)

- Service Worker (vite-plugin-pwa) para cache de assets
- Dexie.js (IndexedDB) para dados offline
- Sync engine: fila de operações offline → replay on reconnect

## Data Flow

```
User Action → Vue Component → Pinia Store → API Service
                                                         │
                                                         ▼
                                                    API Request
                                                         │
                                                         ▼
                                                 Laravel Controller
                                                         │
                                                         ▼
                                                  Form Request (validates)
                                                         │
                                                         ▼
                                                   Service Layer
                                                         │
                                                     ┌───┴───┐
                                                     ▼       ▼
                                                PostgreSQL  Redis
                                                     │
                                                     ▼
                                                Response JSON
                                                         │
                                                         ▼
                                               Vue Component (reactive)
```

## Key Decisions

| Decision | Rationale |
|----------|-----------|
| Sanctum SPA (cookies) over JWT | Simplicidade, segurança CSRF automática, sem gerenciamento de tokens |
| Services layer | Separa lógica de negócio dos controllers, testável isoladamente |
| SoftDeletes em todos os modelos | Audit trail completo, recuperação de dados |
| Redis para cache + queue | Fila única reduz complexidade operacional |
| PrimeVue sobre Vuetify | Visual mais profissional, componentes mais ricos para DataTable/Tree |
| ECharts sobre Chart.js | Performance superior com grandes datasets, mais tipos de gráfico |
| PWA + IndexedDB (Dexie) | Offline-first sem reescrever para mobile (Capacitor é v2) |

## Security

- Authentication: Sanctum SPA (CSRF + session)
- Rate limiting: 5 attempts/minute on login
- CORS: configurado para origens específicas
- Validation: Form Requests com regras específicas por ação
- Audit: todas as operações CRUD registradas
- Soft deletes: dados nunca são permanentemente removidos via API