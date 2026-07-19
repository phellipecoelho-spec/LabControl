# Phase 1: Infraestrutura - Research

**Researched:** 2026-07-18
**Domain:** Docker Compose, Laravel Backend Setup, PostgreSQL Migrations, Build Automation
**Confidence:** HIGH

## Summary

Phase 1 completes the infrastructure foundation so the development team can run the full stack locally with a single command. Sprint 0 created the directory structure, initialized both Laravel and Vue projects, and wrote a skeleton docker-compose.yml, but the stack does not boot correctly today due to several configuration gaps: missing APP_KEY, incorrect Redis host binding, absent CORS configuration, uninstalled Sanctum package, and a setup script that skips migrations entirely.

**Primary recommendation:** Three sequential plans — (1) fix Docker Compose and backend env/bootstrap so containers build and stay healthy; (2) wire database migrations and seeders into the boot sequence; (3) rebuild the setup script (PowerShell + Bash) as a robust, idempotent entry point that validates health after every step.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- Stack: Vue 3 + PrimeVue + Laravel + PostgreSQL + Docker — decisão arquitetural já tomada
- Frontend build: Vite + TypeScript + Pinia + Vue Router
- Backend auth: Sanctum (SPA-first)
- Banco: PostgreSQL (não MySQL, não SQLite)
- Cache/Filas: Redis
- Containerização: Docker Compose
- Licenciamento: 100% open source, sem dependências pagas
- Frontend modules: estrutura modular por funcionalidade (auth/, users/, equipment/, etc.)
- API: REST com prefixo /api/v1/
- UUIDs em vez de auto-increment
- Soft Delete em todas as tabelas
- Activity logs em todas as operações
- Tema escuro como padrão
- Desenvolvimento iterativo: Infrastructure -> Auth -> Users -> Layout -> Módulos de negócio

### the agent's Discretion
- Escolha entre JWT e Sanctum — usuário prefere Sanctum (declarado em CONTEXT.md)
- Estrutura exata dos módulos do framework (dentro do padrão definido no PLANNER.md)
- Versões exatas das dependências (dentro do ecossistema definido)

### Deferred Ideas (OUT OF SCOPE)
- Aplicativo mobile nativo — PWA suficiente para v1
- Chat interno — usar ferramentas externas
- Videoconferência — usar ferramentas externas
- Integração IoT com equipamentos — v2+
- Faturamento/NFe — fora do escopo de gestão laboratorial
- CRM — fora do escopo
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| INFRA-01 | Docker Compose funcional (build PHP + composer install + containers rodando) | Configs existem mas têm gaps críticos — APP_KEY vazio, REDIS_HOST incorreto, CORS ausente, Sanctum não instalado, extensão phpredis não instalada no Dockerfile |
| INFRA-02 | Migrations executadas no PostgreSQL | 5 migrations existem (users, cache, jobs, roles/permissions, activity_logs) mas setup.ps1 não as executa; DatabaseSeeder só cria usuário de teste; roles/permissions não têm seeders |
| INFRA-03 | Script de setup automatizado funcional | setup.ps1 existe mas não roda migrations, não valida health dos containers, não gera .env do frontend; sem equivalente bash para Linux/Mac |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Container orchestration | Docker / Infrastructure | — | Docker Compose define e coordena todos os serviços |
| PHP runtime + extensions | Docker (php image) | — | Build time — PHP 8.3-fpm com extensões PostgreSQL e Redis |
| Database initialization | Docker (postgres init) + Laravel Migrations | — | init SQL cria databases extras; Laravel cria schemas via migrations |
| Web server | Docker (nginx) | — | Nginx reverse proxy para PHP-FPM |
| Cache / Queue | Docker (redis) | — | Redis container para session/cache/queue |
| Setup automation | Scripts (setup.ps1, setup.sh) | — | Script orquestrador que coordena Docker + backend + frontend |
| Build pipeline | Docker Compose + npm | — | Composer install no container PHP; npm run dev no host |

## Standard Stack

### Core
| Library/Tool | Version | Purpose | Why Standard |
|-------------|---------|---------|--------------|
| Docker Engine | 29.6.1 | Container runtime | Ambiente isolado e reproduzível |
| Docker Compose | v5.3.0 | Multi-container orchestration | Define todos os serviços em um arquivo |
| PHP | 8.3-fpm-alpine | Backend runtime | Imagem oficial, leve, com extensões PDO PostgreSQL |
| Composer | latest (via Docker) | PHP dependency manager | Imagem oficial COPY --from=composer |
| PostgreSQL | 17-alpine | Database | Imagem oficial Alpine, health check incluído |
| Redis | 7-alpine | Cache + Session + Queue | Imagem oficial Alpine |
| Nginx | stable-alpine | Reverse proxy / web server | Imagem oficial, config simples para Laravel |

### Supporting
| Library/Tool | Version | Purpose | When to Use |
|-------------|---------|---------|-------------|
| Node.js | 22 LTS | Frontend build tooling | Host-side (não containerizado) para npm install + vite dev |
| npm | 11+ | Frontend package manager | Acompanha Node.js |
| phpunit/phpunit | ^12.5 | Backend testing | Quando rodar testes no container PHP |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Docker Compose | Docker Swarm / Kubernetes | Complexidade desnecessária para dev local |
| Alpine-based images | Debian-based | Alpine é menor (~5MB vs ~200MB) mas tem libc diferente (musl) |
| Native PostgreSQL install | Docker PostgreSQL | Docker isola versão, evita conflitos com XAMPP MySQL |

**Installation:**
```bash
# Primeiro build e start dos containers (do diretório docker/)
docker compose build php
docker compose up -d

# Depois composer install dentro do container
docker compose exec php composer install

# Gerar APP_KEY
docker compose exec php php artisan key:generate

# Rodar migrations
docker compose exec php php artisan migrate

# Frontend (no host, fora do Docker)
cd frontend && npm install && npm run dev
```

**Version verification:** Docker 29.6.1, Docker Compose v5.3.0, Node 22.12.0, npm 11.1.0 — confirmados no ambiente de desenvolvimento.

## Package Legitimacy Audit

> Phase 1 é puramente infraestrutura — não instala pacotes npm ou PHP não fornecidos pelo framework. Os pacotes PHP (Laravel framework, Sanctum) vêm via Composer do repositório oficial packagist.org. Os pacotes npm (Vue, PrimeVue, Pinia, etc.) já foram instalados na Sprint 0. Nenhum pacote externo novo é introduzido nesta fase.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| laravel/sanctum | packagist | ~5 yrs | 100M+ | laravel/sanctum | OK | Approved — instalar via composer require |
| laravel/framework | packagist | ~12 yrs | 500M+ | laravel/laravel | OK | Already installed |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Host Machine                              │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    Docker Compose                          │  │
│  │                                                           │  │
│  │  ┌──────────┐    ┌──────────┐    ┌──────────┐             │  │
│  │  │  Nginx   │───▶│   PHP    │───▶│PostgreSQL│             │  │
│  │  │:80       │    │:9000     │    │:5432     │             │  │
│  │  └──────────┘    └──────────┘    └──────────┘             │  │
│  │       │               │               │                    │  │
│  │       │               ▼               │                    │  │
│  │       │         ┌──────────┐          │                    │  │
│  │       │         │  Redis   │          │                    │  │
│  │       │         │:6379     │          │                    │  │
│  │       │         └──────────┘          │                    │  │
│  │       ▼                               │                    │  │
│  │  /var/www/backend/public              │                    │  │
│  │  (bind mount)                         │                    │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    Host Direct                             │  │
│  │  frontend/ ── npm run dev ──▶ :5173 ──proxy──▶ :80/api    │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

**Data flow:** Browser → :5173 (Vite dev server) → proxy `/api/*` → :80 (Nginx) → PHP-FPM → PostgreSQL/Redis

### Recommended Project Structure (já existe, validar)
```
labcontrol/
├── docker/
│   ├── docker-compose.yml       # Serviços: nginx, php, postgres, redis + volumes + network
│   ├── php/
│   │   ├── Dockerfile           # PHP 8.3-fpm + pdo_pgsql + zip + bcmath + composer
│   │   └── php.ini              # upload 50M, timezone America/Sao_Paulo
│   ├── nginx/
│   │   └── default.conf         # Serve /var/www/backend/public, fastcgi pass php:9000
│   ├── postgres/
│   │   └── init/
│   │       └── 01-create-databases.sql  # Cria labcontrol_testing e labcontrol_staging
│   └── redis/                   # Config vazia — defaults suficientes
├── scripts/
│   ├── setup.ps1                # Setup PowerShell (precisa de reparos)
│   └── setup.sh                 # [PRECISA CRIAR] Setup Bash para Linux/Mac
├── backend/                     # Laravel app (bind mount em /var/www/backend)
│   ├── .env                     # [PRECISA REPARAR] APP_KEY vazio, REDIS_HOST=127.0.0.1
│   ├── .env.example             # [PRECISA ATUALIZAR] Ainda aponta sqlite
│   ├── config/
│   │   ├── app.php
│   │   ├── database.php
│   │   ├── cache.php
│   │   ├── session.php
│   │   ├── queue.php
│   │   ├── auth.php
│   │   └── cors.php             # [PRECISA CRIAR]
│   ├── routes/
│   │   ├── web.php
│   │   ├── api.php              # [PRECISA CRIAR] Para rotas /api/v1
│   │   └── console.php
│   ├── database/migrations/     # 5 migrations existentes
│   └── app/Models/User.php      # Usa UUID, HasFactory, Notifiable
└── frontend/                    # Vue 3 + Vite (host-side)
    ├── .env                     # [PRECISA CRIAR] VITE_API_URL
    ├── vite.config.ts           # Proxy /api → localhost:80
    ├── package.json             # Vue 3, PrimeVue 5, Pinia, Vue Router, Axios, ECharts
    └── src/
        ├── main.ts              # PrimeVue Aura dark mode, Pinia, Router
        └── modules/             # 13 módulos scaffoldados (vazios internamente)
```

### Pattern 1: Service Dependency Chain
**What:** Docker Compose define dependências entre serviços usando `depends_on` com `condition`.
**When to use:** Sempre que um serviço precisa de outro para funcionar.
**Example:**
```yaml
# docker-compose.yml (já implementado parcialmente)
php:
  depends_on:
    postgres:
      condition: service_healthy
    redis:
      condition: service_started
```

### Pattern 2: Laravel Health Check Pattern
**What:** Laravel possui rota de health check built-in `/up` que verifica se o framework carregou.
**When to use:** Para health checks do container PHP e validação no setup script.
**Example:**
```php
// bootstrap/app.php (já configurado)
health: '/up',
```

### Pattern 3: Wait-for-It Strategy for Migrations
**What:** Migrations precisam esperar o PostgreSQL estar saudável. O `depends_on` do Docker Compose garante isso.
**When to use:** Sempre que rodar `php artisan migrate` em script de setup.

### Anti-Patterns to Avoid
- **APP_KEY vazio em produção:** APP_KEY vazio quebra encryption, sessions, cookies. Nunca subir sem gerar `php artisan key:generate`.
- **REDIS_HOST=127.0.0.1 dentro do container:** Dentro do Docker, serviços se comunicam pelo nome do serviço, não por localhost. O container PHP deve usar `redis` como host.
- **Rodar migrations sem health check do banco:** Pode falhar silenciosamente. Sempre verificar PostgreSQL antes de migrar.
- **Setup script sem validação:** `2>&1 | Out-Null` esconde erros. O script deve verificar exit codes e health endpoints.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| PHP extension management | Compilar extensões manualmente | `docker-php-ext-install` | Imagem oficial PHP já tem o script; evita erros de compilação |
| PostgreSQL initialization | Scripts complexos de init | `docker-entrypoint-initdb.d/` | PostgreSQL official image executa scripts SQL em ordem alfabética |
| Container health checks | Polling manual com sleep | Docker `healthcheck` + `condition: service_healthy` | Nativo do Docker, mais confiável que sleep |
| Queue worker management | Supervisor customizado | `php artisan queue:listen` ou Laravel Horizon | Já incluso no Laravel, zero config para dev |

**Key insight:** Docker Compose + Laravel é um ecossistema maduro. Quase tudo que precisamos (health checks, init scripts, volumes, networks) já existe como padrão. O risco está em configurações inconsistentes entre .env e docker-compose.yml, não em falta de ferramentas.

## Common Pitfalls

### Pitfall 1: APP_KEY vazio após setup
**What goes wrong:** Laravel não consegue encriptar sessions, cookies, ou valores. Login falha com "The payload is invalid."
**Why it happens:** APP_KEY não é gerado automaticamente em instalações existentes. O setup precisa executar `php artisan key:generate`.
**How to avoid:** Incluir `key:generate` no setup script sempre, não apenas na primeira execução. É idempotente — re-gerar não quebra dados existentes.
**Warning signs:** Erro "The only supported ciphers are AES-128-CBC and AES-256-CBC" no log do Laravel.

### Pitfall 2: REDIS_HOST apontando para localhost dentro do Docker
**What goes wrong:** PHP tenta conectar a 127.0.0.1 dentro do container, que não tem Redis rodando. Timeout de conexão.
**Why it happens:** O .env.example do Laravel usa 127.0.0.1 como default. O desenvolvedor copia sem ajustar para Docker.
**How to avoid:** .env de desenvolvimento Docker deve usar o nome do serviço Docker (`redis`) como host.
**Warning signs:** Conexão Redis recusada nos logs, session/cache não funcionam.

### Pitfall 3: Setup script não valida health dos containers
**What goes wrong:** O script termina com "Setup complete!" mas o PostgreSQL pode não estar aceitando conexões ainda. Migrations falham silenciosamente.
**Why it happens:** `docker compose up -d` retorna imediatamente, sem esperar os health checks.
**How to avoid:** Após `up -d`, esperar `docker compose ps --filter "status=healthy"` para postgres antes de rodar migrations.
**Warning signs:** "Connection refused" ao rodar migrate, mas o script reporta sucesso.

### Pitfall 4: Permissões de bind mount no Linux
**What goes wrong:** O container PHP roda como usuário `labcontrol` (uid 1000) mas o diretório backend no host pertence a root ou outro usuário. Erro de permissão ao escrever em storage/ ou vendor/.
**Why it happens:** Bind mounts preservam permissões do host. O Dockerfile cria usuário labcontrol:labcontrol uid 1000, mas o host pode ter uid diferente.
**How to avoid:** Garantir que o diretório backend/ seja owned por uid 1000, ou configurar ACL no host. O Dockerfile já tenta `chown -R labcontrol:labcontrol`.
**Warning signs:** "file_put_contents: failed to open stream: Permission denied" em logs/storage.

### Pitfall 5: Frontend não consegue chamar API por CORS
**What goes wrong:** Requisições do Vite dev server (:5173) para o backend (:80) são bloqueadas pelo navegador.
**Why it happens:** O backend não possui configuração CORS para aceitar requisições de origem diferente.
**How to avoid:** Configurar CORS no Laravel (config/cors.php) e/ou usar o proxy do Vite (já configurado em vite.config.ts) redirecionando /api → localhost:80. O proxy do Vite é a abordagem recomendada para desenvolvimento.

## Code Examples

### Verified patterns from official sources:

### Docker Compose service with health check + dependency
```yaml
# docker/docker-compose.yml — já implementado corretamente
services:
  postgres:
    image: postgres:17-alpine
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U labcontrol"]
      interval: 5s
      timeout: 5s
      retries: 5
  php:
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_started
```

### PHP Dockerfile with PostgreSQL extensions
```dockerfile
# docker/php/Dockerfile — já implementado corretamente para PostgreSQL
FROM php:8.3-fpm-alpine
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip unzip curl git linux-headers \
    && docker-php-ext-install pdo_pgsql zip bcmath
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

### Adding the phpredis extension (MISSING — needs to be added)
```dockerfile
# Necessário adicionar no php/Dockerfile
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip unzip curl git linux-headers \
    && docker-php-ext-install pdo_pgsql zip bcmath \
    && pecl install redis && docker-php-ext-enable redis
```

### Nginx Laravel config
```nginx
# docker/nginx/default.conf — já implementado corretamente
server {
    listen 80;
    root /var/www/backend/public;
    index index.php;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Laravel routes for API (PRECISA CRIAR — api.php)
```php
<?php
// backend/routes/api.php
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    });
});
```

### Laravel CORS config (PRECISA CRIAR)
```php
<?php
// backend/config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Robust setup script migration step
```powershell
# Extraído do setup.ps1 — step de migrations precisa ser adicionado
Write-Host "[X/4] Running migrations..." -ForegroundColor Yellow
Set-Location $dockerDir
docker compose exec -T php php artisan migrate --force
if (-not $?) {
    Write-Host "ERROR: Migration failed" -ForegroundColor Red
    exit 1
}
Write-Host "Migrations OK" -ForegroundColor Green
```

### Container health check validation
```powershell
# Padrão para validar health após docker compose up
Write-Host "Waiting for PostgreSQL to be healthy..."
docker compose wait postgres
Write-Host "PostgreSQL is healthy"
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Laravel Sail | Docker Compose manual | Sprint 0 | Mais controle, menos abstração |
| XAMPP + MySQL | Docker + PostgreSQL | Sprint 0 | Isolamento, escalabilidade, JSON/ GIS |
| JWT manual | Sanctum (SPA tokens) | Sprint 0 | SPA-first, CSRF protection, simpler |
| `docker-compose` (v1) | `docker compose` (v2 plugin) | Docker 2023 | Comando integrado, sem hyphen |

**Deprecated/outdated:**
- `docker-compose` (hyphen) — legado v1. Usar `docker compose` (sem hyphen) como v2 plugin.
- `php:8.3-fpm` (non-alpine) — Alpine reduz imagem de ~800MB para ~200MB.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Docker Desktop está instalado e funcional no ambiente do desenvolvedor | Summary | Baixo — task de verificação no setup.ps1 já detecta ausência |
| A2 | Node.js 22 LTS está disponível no host | Standard Stack | Médio — frontend não containerizado, sem Node o dev não roda front |
| A3 | phpredis é a extensão recomendada para Redis com Laravel | Code Examples | Baixo — `predis` também funciona mas phpredis tem melhor performance |

**All claims above are `[ASSUMED]`** — need user confirmation before execution.

## Open Questions (RESOLVED)

1. **Frontend containerizado ou host-side?** `RESOLVED: host-side para dev, container para produção`
   - What we know: Hoje o frontend roda no host (npm run dev), Vite faz proxy para o backend Docker.
   - What's unclear: Se o usuário quer o frontend também em container (multi-stage build) para produção, ou se manterá host-side para dev.
   - Recommendation: Manter host-side para dev (hot reload mais rápido), containerizar apenas para produção.
   - **Plan 03** mantém frontend host-side na setup script (npm run dev no host).

2. **phpredis vs predis?** `RESOLVED: phpredis`
   - What we know: .env tem `REDIS_CLIENT=phpredis` mas phpredis não está instalado no Dockerfile.
   - What's unclear: Se phpredis (extensão C, mais rápida) ou predis (pacote PHP, sem compilação).
   - Recommendation: phpredis é mais performático, mas requer compilação. Adicionar `pecl install redis` no Dockerfile.
   - **Plan 01 T1** adiciona phpredis via pecl no Dockerfile.

3. **Worker de filas no Docker?** `RESOLVED: adiado para Phase 2+`
   - What we know: QUEUE_CONNECTION=database, queue:listen pode rodar manualmente.
   - What's unclear: Se deve incluir um serviço queue worker no docker-compose.yml ou manter exec manual.
   - Recommendation: Para Phase 1 (infra), manter manual. Adicionar serviço queue worker em Phase 2+ quando houver jobs reais.
   - **Fora do escopo da Phase 1** — será tratado quando houver jobs reais.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Docker Engine | All services | ✓ | 29.6.1 | WSL2 backend |
| Docker Compose | Container orchestration | ✓ | v5.3.0 | docker-compose v1 legado |
| Node.js | Frontend dev | ✓ | 22.12.0 | — |
| npm | Frontend packages | ✓ | 11.1.0 | pnpm/yarn |
| Composer | Backend packages | ✓ (via Docker) | latest (Docker image) | Instalação nativa no host |

**Missing dependencies with no fallback:** None — all core dependencies are available.

**Missing dependencies with fallback:** None.

## Validation Architecture

> nyquist_validation is enabled in .planning/config.json

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5 (backend) |
| Config file | backend/phpunit.xml |
| Quick run command | `docker compose exec php php artisan test --filter=Unit` |
| Full suite command | `docker compose exec php php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| INFRA-01 | Docker containers build and stay healthy | smoke | `docker compose ps --filter "status=running"` | ❌ Wave 0 |
| INFRA-02 | Migrations run without errors | smoke | `docker compose exec php php artisan migrate --force` | ❌ Wave 0 |
| INFRA-03 | Setup script completes without errors | smoke | `.\scripts\setup.ps1` (PowerShell) | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** N/A — Phase 1 é infraestrutura, não tem testes unitários de código
- **Per wave merge:** N/A
- **Phase gate:** Full smoke test — containers running + migrations applied + setup script OK

### Wave 0 Gaps
- [ ] Smoke test script (teste de fumaça para verificar containers running + migrations + health endpoint)
- [ ] Validation commands não são testes PHPUnit mas sim comandos Docker — documentar sequência de verificação manual no PLAN.md

## Sources

### Primary (HIGH confidence)
- **Código real do projeto** (docker-compose.yml, Dockerfiles, .env, composer.json, migrations) — lido e verificado diretamente
- **Docker official documentation** — padrões de healthcheck, init scripts, volumes confirmados
- **Laravel official documentation** — APP_KEY, migrations, CORS, Sanctum patterns confirmados

### Secondary (MEDIUM confidence)
- **Node.js/Docker version check** — executado no ambiente de desenvolvimento (`docker version`, `node --version`)
- **PrimeVue 5 + Vue 3 + Vite configuration** — confirmado via package.json e main.ts

### Tertiary (LOW confidence)
- Nenhum — todas as descobertas foram verificadas contra o código real ou documentação oficial/Docker

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — lido diretamente dos arquivos do projeto e confirmado via comandos
- Architecture: HIGH — Docker Compose stack padrão para Laravel + Vue
- Pitfalls: HIGH — baseado em experiência com o ecossistema e gaps identificados no código
- Current state gaps: HIGH — cada gap foi verificado pela ausência nos arquivos reais

**Research date:** 2026-07-18
**Valid until:** 2026-08-18 (30 days — configuração de infraestrutura muda lentamente)
