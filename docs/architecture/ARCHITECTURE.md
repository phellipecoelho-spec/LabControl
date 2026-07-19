# Arquitetura do LabControl

## Visão Geral

```
Frontend (Vue 3 + PrimeVue)
        |
        | HTTP REST API
        v
Backend (Laravel 12)
        |
        | ORM (Eloquent)
        v
PostgreSQL 17
        |
        v
Storage (Arquivos)
```

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Frontend | Vue 3 + Vite + TypeScript + PrimeVue |
| Estado | Pinia |
| Roteamento | Vue Router |
| API Client | Axios |
| Gráficos | Apache ECharts |
| Backend | Laravel 12 + PHP 8.3 |
| Autenticação | Laravel Sanctum |
| Banco | PostgreSQL 17 |
| Cache/Filas | Redis |
| Containerização | Docker Compose |

## Estrutura de Módulos

Cada módulo de negócio é independente e contém:

```
module/
  components/    # Componentes Vue específicos
  pages/         # Páginas do módulo
  services/      # Chamadas API
  store/         # Estado Pinia
  types/         # Tipos TypeScript
  routes/        # Rotas do módulo
  composables/   # Composables Vue
```

## Convenções

- UUIDs como chaves primárias
- Soft delete em todas as tabelas
- Logs de auditoria em todas as operações
- snake_case para banco de dados
- API versionada (/api/v1/)
