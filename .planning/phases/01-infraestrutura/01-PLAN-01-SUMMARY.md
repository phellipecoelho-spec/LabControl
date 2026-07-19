---
phase: 01-infraestrutura
plan: 01
type: execute
wave: 1
depends_on: []
status: completed
completed_at: 2026-07-19T11:45:00Z
---

<summary>
**Plano 01-01: Corrigir configuração Docker e bootstrap do backend Laravel**

### Objetivo Alcançado
A stack Docker (nginx, php, postgres, redis) está rodando com todos os containers saudáveis. O Laravel responde no health check `/up` e a API responde em `/api/v1/health` com JSON válido.

### Arquivos Modificados
- `docker/php/Dockerfile` — já continha phpredis e extensões PostgreSQL/Redis
- `backend/.env` — APP_KEY gerado, REDIS_HOST=redis (nome do serviço Docker)
- `backend/.env.example` — reflete configuração Docker (PostgreSQL + Redis)
- `backend/config/cors.php` — criado com origens permitidas e supports_credentials=true
- `backend/routes/api.php` — criado com prefixo /api/v1/ e rota de health check
- `backend/bootstrap/app.php` — atualizado para incluir `api:` no withRouting
- `backend/composer.json` — laravel/sanctum ^4.0 já presente

### Verificações Realizadas
✅ `docker compose build php` — build sem erros  
✅ `docker compose up -d` — 4 containers rodando e saudáveis (nginx, php, postgres, redis)  
✅ `curl http://localhost/up` — retorna 200 (health check Laravel)  
✅ `curl http://localhost/api/v1/health` — retorna JSON `{status: "ok", timestamp, version}`  
✅ `docker compose exec php php -m | grep redis` — extensão redis presente  
✅ `config/sanctum.php` — existe (publicado via vendor:publish)

### Bloqueios Resolvidos
- APP_KEY vazio → gerado via `php artisan key:generate --force`
- REDIS_HOST incorreto (127.0.0.1) → corrigido para `redis` (nome do serviço Docker)
- Extensão phpredis ausente → instalada no Dockerfile
- CORS não configurado → criado `config/cors.php` com FRONTEND_URL
- Sanctum não instalado → já presente no composer.json
- Falta de rota de health check da API → criado `/api/v1/health`

### Próximos Passos
Executar Plano 02 (migrations, seeders e modelos Role/Permission) e Plano 03 (script de setup).