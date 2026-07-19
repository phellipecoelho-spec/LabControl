---
phase: 01-infraestrutura
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - docker/php/Dockerfile
  - backend/.env
  - backend/.env.example
  - backend/composer.json
  - backend/composer.lock
  - backend/config/cors.php
  - backend/routes/api.php
  - backend/bootstrap/app.php
autonomous: false
requirements:
  - INFRA-01
user_setup:
  - service: Docker Desktop
    why: "Necessário para build e execução dos containers"
    env_vars: []
    dashboard_config: []

must_haves:
  truths:
    - "docker compose build php conclui sem erros"
    - "containers nginx, php, postgres, redis sobem e ficam saudáveis"
    - "Laravel responde na porta 80 com health check /up"
    - "API responde em /api/v1/health com JSON"
    - "Redis acessível pelo container PHP via hostname redis"
    - "Sanctum instalado e disponível"
    - "CORS configurado para aceitar requisições do frontend em :5173"
  artifacts:
    - docker/php/Dockerfile (atualizado com phpredis)
    - backend/.env (APP_KEY gerado, REDIS_HOST=redis)
    - backend/.env.example (atualizado para PostgreSQL + Redis Docker)
    - backend/config/cors.php (criado)
    - backend/routes/api.php (criado)
    - backend/composer.json (com sanctum adicionado)
  key_links:
    - "Dockerfile → phpredis: container PHP consegue conectar no Redis"
    - ".env → REDIS_HOST=redis: nome do serviço Docker, não 127.0.0.1"
    - "config/cors.php → allowed_origins: frontend consegue chamar API sem bloqueio CORS"
    - "routes/api.php → /api/v1/health: primeiro endpoint validando que Laravel + DB + Redis estão operacionais"
---

<objective>
Corrigir a configuração Docker e o bootstrap do backend Laravel para que a stack suba corretamente com todos os serviços saudáveis.

**Purpose:** A infraestrutura é a fundação de todo o projeto. Sem containers funcionais, nenhum módulo pode ser desenvolvido ou testado. Este plano corrige os gaps críticos identificados na Sprint 0: APP_KEY vazio, REDIS_HOST incorreto, extensão phpredis ausente, CORS não configurado, Sanctum não instalado, e falta de rota de health check da API.

**Output:**
- Dockerfile com phpredis e todas as extensões PostgreSQL/Redis
- .env com APP_KEY gerado e REDIS_HOST=redis (nome do serviço Docker)
- .env.example atualizado para refletir configuração Docker (PostgreSQL + Redis)
- config/cors.php criado para permitir requisições do frontend Vite
- routes/api.php criado com prefixo /api/v1/ e rota de health check
- composer.json com laravel/sanctum adicionado
- Containers buildados e rodando (nginx, php, postgres, redis)
- Laravel health check /up e /api/v1/health respondendo
</objective>

<execution_context>
@.planning/workflows/execute-plan.md
@.planning/templates/summary.md
</execution_context>

<context>
@.planning/PROJECT.md
@.planning/ROADMAP.md
@.planning/STATE.md
@.planning/phases/01-infraestrutura/01-RESEARCH.md
@docker/docker-compose.yml
@docker/php/Dockerfile
@docker/nginx/default.conf
@backend/.env
@backend/.env.example
@backend/composer.json
@backend/config/session.php
@backend/config/cache.php
@backend/config/database.php
@backend/bootstrap/app.php
@frontend/vite.config.ts
</context>

<tasks>

<task type="auto">
  <name>Task 1: Corrigir Dockerfile — adicionar phpredis e garantir extensões</name>
  <files>
    docker/php/Dockerfile
  </files>
  <action>
    Atualizar docker/php/Dockerfile para:
    1. Adicionar `pecl install redis && docker-php-ext-enable redis` ao RUN existente — o .env já usa `REDIS_CLIENT=phpredis` mas a extensão não está instalada (per D-03, Redis é cache/session driver obrigatório).
    2. Adicionar `oniguruma-dev` e `libpng-dev` para evitar warnings de extensões faltantes em instalações futuras de pacotes Composer.
    3. Manter a estrutura existente (apk add, docker-php-ext-install, COPY composer, WORKDIR, usuário labcontrol).

    A ordem final do RUN deve ser:
    - apk add postgresql-dev, libzip-dev, oniguruma-dev, libpng-dev, zip, unzip, curl, git, linux-headers
    - docker-php-ext-install pdo_pgsql, zip, bcmath, gd
    - pecl install redis && docker-php-ext-enable redis

    IMPORTANTE: Não remover a criação do usuário labcontrol (uid 1000) nem o WORKDIR /var/www/backend.
  </action>
  <verify>
    <automated>docker compose build php 2>&1</automated>
  </verify>
  <done>docker compose build php conclui sem erros. Verificar log: extensão redis habilitada, extensão pdo_pgsql presente, sem warnings fatais.</done>
</task>

<task type="auto" tdd="false">
  <name>Task 2: Corrigir .env e .env.example — APP_KEY, REDIS_HOST, PostgreSQL</name>
  <files>
    backend/.env
    backend/.env.example
  </files>
  <action>
    Atualizar backend/.env.example para refletir a configuração Docker de desenvolvimento:
    1. `APP_NAME=LabControl` (já está no .env mas .env.example tem Laravel)
    2. `APP_ENV=local`
    3. `APP_KEY=` (deixar vazio — será gerado via `php artisan key:generate` no setup)
    4. `APP_DEBUG=true` e `APP_URL=http://localhost`
    5. `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR`
    6. `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `DB_PORT=5432`, `DB_DATABASE=labcontrol`, `DB_USERNAME=labcontrol`, `DB_PASSWORD=labcontrol`
    7. `SESSION_DRIVER=redis`, `SESSION_LIFETIME=120`
    8. `CACHE_STORE=redis`
    9. `QUEUE_CONNECTION=database`
    10. `REDIS_CLIENT=phpredis`, `REDIS_HOST=redis`, `REDIS_PASSWORD=null`, `REDIS_PORT=6379`
    — CRÍTICO: REDIS_HOST deve ser `redis` (nome do serviço Docker), não `127.0.0.1` (dentro do container PHP não tem Redis em localhost).
    11. Adicionar `FRONTEND_URL=http://localhost:5173` — usado pelo config/cors.php
    12. Manter demais configs (mail, AWS, etc.) com valores seguros para dev

    Em backend/.env:
    - Garantir que APP_KEY está vazio (será gerado via `php artisan key:generate` após `composer install`)
    - Garantir que REDIS_HOST=redis (já está como 127.0.0.1, corrigir para redis)
    - Garantir DB_CONNECTION=pgsql e credenciais corretas
    - Adicionar FRONTEND_URL=http://localhost:5173 se não existir
  </action>
  <verify>
    <automated>Select-String -Pattern "REDIS_HOST=redis" -LiteralPath "backend\.env.example"; if ($?) { Write-Host "OK" }</automated>
  </verify>
  <done>.env.example reflete configuração Docker (PostgreSQL, Redis via service name). .env tem REDIS_HOST=redis e APP_KEY vazio. Ambos os arquivos são válidos como arquivos de ambiente Laravel.</done>
</task>

<task type="auto">
  <name>Task 3: Instalar Sanctum, configurar CORS e rota de health check da API</name>
  <files>
    backend/composer.json
    backend/composer.lock
    backend/config/cors.php
    backend/routes/api.php
    backend/bootstrap/app.php
  </files>
  <action>
    Esta task tem 4 sub-etapas:

    **3a. Instalar Sanctum via Composer:**
    Adicionar `"laravel/sanctum": "^4.0"` ao `require` do composer.json (per D-03, Sanctum é o mecanismo de autenticação decidido para o projeto Sanctum é SPA-first, mais simples que JWT manual). Após adicionar, rodar `docker compose run --rm php composer install --no-interaction` para atualizar composer.lock e baixar o pacote.
    NÃO alterar nenhuma outra dependência existente.

    **3b. Publicar config do Sanctum:**
    Rodar `docker compose run --rm php php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` para criar config/sanctum.php.

    **3c. Criar backend/config/cors.php:**
    ```php
    <?php
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
    Isso permite que o Vite dev server (:5173) chame a API sem bloqueio CORS. O `supports_credentials: true` é necessário para Sanctum (envio de cookies de sessão).

    **3d. Criar backend/routes/api.php:**
    ```php
    <?php
    use Illuminate\Support\Facades\Route;

    Route::prefix('v1')->group(function () {
        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now(),
                'version' => '1.0.0',
            ]);
        });
    });
    ```

    **3e. Atualizar backend/bootstrap/app.php:**
    Adicionar a rota de API ao `withRouting`:
    ```php
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ```
    O parâmetro `api:` registra as rotas de api.php com prefixo /api automaticamente (Laravel 11+), resultando em /api/v1/health.
  </action>
  <verify>
    <automated>
      docker compose run --rm php php artisan route:list --path=api/v1/health 2>&1; if ($?) { Write-Host "Sanctum configurado e rota health OK" }
    </automated>
  </verify>
  <done>
    - `composer.json` tem laravel/sanctum ^4.0 no require
    - `config/cors.php` existe com origens permitidas e supports_credentials=true
    - `routes/api.php` existe com GET /api/v1/health retornando JSON {status, timestamp, version}
    - `bootstrap/app.php` inclui `api:` no withRouting
    - `docker compose run --rm php php artisan route:list` mostra a rota api.v1.health
    - GET /api/v1/health retorna 200 com JSON válido
  </done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| host → Docker containers | Portas expostas (80, 5432, 6379) podem ser acessadas da rede local |
| frontend (:5173) → backend (:80) | Requisições HTTP do Vite dev server para a API |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-01-01 | Tampering | Dockerfile (phpredis) | low | accept | phpredis vêm do repositório oficial pecl.php.net — risco baixo de supply chain |
| T-01-02 | Tampering | composer.json (sanctum) | low | accept | laravel/sanctum é um pacote oficial Laravel do packagist.org — 100M+ downloads |
| T-01-03 | Information Disclosure | .env (APP_KEY, DB_PASSWORD) | medium | mitigate | .env está no .gitignore do Laravel; .env.example não contém secrets reais |
| T-01-04 | Elevation of Privilege | CORS config | medium | mitigate | allowed_origins restrito a FRONTEND_URL (localhost:5173 em dev); supports_credentials exige origem explícita |
| T-01-05 | Spoofing | Redis sem senha | low | accept | Rede Docker interna isolada; Redis não exposto externamente; apenas containers no mesmo network acessam |
</threat_model>

<verification>
1. `docker compose build php` — build sem erros
2. `docker compose up -d` — todos os containers saudáveis (nginx, php, postgres, redis)
3. `docker compose ps --filter "status=running"` — 4 containers running
4. `curl http://localhost/up` — retorna 200 (health check Laravel)
5. `curl http://localhost/api/v1/health` — retorna JSON com status "ok"
6. `docker compose exec php php -m | Select-String "redis"` — extensão redis presente
7. `docker compose exec php php artisan sanctum:check` — Sanctum configurado (se comando existir) OU checar presença de config/sanctum.php
</verification>

<success_criteria>
- [ ] Docker build PHP conclui sem erros
- [ ] Todos os 4 containers (nginx, php, postgres, redis) estão running e saudáveis
- [ ] GET http://localhost/up retorna 200 (Laravel health check)
- [ ] GET http://localhost/api/v1/health retorna JSON {status: "ok"}
- [ ] Config CORS criado com supports_credentials=true e FRONTEND_URL como origem
- [ ] Sanctum instalado via composer e configurado
- [ ] .env com REDIS_HOST=redis e APP_KEY vazio (pronto para gerar)
</success_criteria>

<output>
Criar `.planning/phases/01-infraestrutura/01-PLAN-01-SUMMARY.md` quando concluído
</output>
