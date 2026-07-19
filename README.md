# LabControl

Plataforma modular de gestão laboratorial.

## Stack

| Camada | Tecnologia |
|---|---|
| **Frontend** | Vue 3 + Vite + TypeScript + PrimeVue |
| **Backend** | Laravel 12 + PHP 8.3 |
| **Banco** | PostgreSQL 17 |
| **Cache/Filas** | Redis |
| **Containerização** | Docker Compose |

## Estrutura

```
labcontrol/
├── frontend/       # SPA Vue 3
├── backend/        # API Laravel
├── database/       # Migrations e scripts SQL
├── docker/         # Configuração Docker
├── docs/           # Documentação
├── scripts/        # Automações
└── backups/        # Dumps do banco
```

## Requisitos

- Docker + Docker Compose
- Node.js 22 LTS
- Git

## Desenvolvimento

```bash
# Subir os containers
docker compose -f docker/docker-compose.yml up -d

# Instalar dependências do backend
docker compose -f docker/docker-compose.yml exec php composer install

# Migrações
docker compose -f docker/docker-compose.yml exec php php artisan migrate

# Frontend
cd frontend
npm install
npm run dev
```

## Roadmap

Consultar `docs/sprints/` para o roadmap completo.

## Licença

MIT
