---
phase: 01-infraestrutura
plan: 03
type: execute
wave: 3
depends_on:
  - 02
files_modified:
  - scripts/setup.ps1
  - scripts/setup.sh
autonomous: false
requirements:
  - INFRA-03
user_setup:
  - service: Docker Desktop
    why: "Necessário para executar o setup completo"
    env_vars: []

must_haves:
  truths:
    - "setup.ps1 executa do zero em um ambiente limpo e produz todos os containers saudáveis"
    - "setup.ps1 valida health dos containers a cada etapa e reporta erros com exit code != 0"
    - "setup.ps1 executa docker compose build, docker compose up -d, composer install, key:generate, migrate, db:seed, storage:link, npm install"
    - "setup.ps1 não esconde erros (sem 2>&1 | Out-Null em passos críticos)"
    - "setup.sh equivalente existe e funciona em Linux/Mac"
    - "setup.sh usa bash shebang e comandos Docker equivalentes"
    - "Ambos os scripts são idempotentes (podem ser executados múltiplas vezes)"
  artifacts:
    - scripts/setup.ps1 (reescrito)
    - scripts/setup.sh (criado)
  key_links:
    - "setup.ps1 → docker compose build: constrói imagem PHP com phpredis e extensões"
    - "setup.ps1 → docker compose up -d: sobe todos os containers"
    - "setup.ps1 → docker compose wait postgres: aguarda PostgreSQL saudável antes de migrar"
    - "setup.ps1 → php artisan migrate --seed: executa migrations + seeders"
    - "setup.ps1 → docker compose ps: valida health de todos os containers no final"
    - "setup.sh: espelho funcional do setup.ps1 para ambiente Linux/Mac"
---

<objective>
Reescrever o script de setup (PowerShell) para ser robusto, com health checks, validação de erros e fluxo completo. Criar versão Bash equivalente para Linux/Mac.

**Purpose:** O setup.ps1 atual é frágil — usa `2>&1 | Out-Null` que esconde erros, não executa migrations, não valida health dos containers, e não tem versão para Linux/Mac. Um setup robusto é essencial para onboarding de novos desenvolvedores, CI/CD futuro, e deploys.

**Output:**
- scripts/setup.ps1 reescrito com: validação de pré-requisitos, build, up, wait-for-health, composer install, key:generate, migrate --seed, storage:link, npm install, validação final
- scripts/setup.sh criado com funcionalidade equivalente
- Ambos os scripts testados e funcionando (setup.ps1 validado no Windows, setup.sh validado conceitualmente)
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
@scripts/setup.ps1
@docker/docker-compose.yml
@docker/php/Dockerfile
@backend/.env
@backend/.env.example
@backend/composer.json
</context>

<tasks>

<task type="auto">
  <name>Task 1: Reescrever setup.ps1 com health checks, validação e fluxo completo</name>
  <files>scripts/setup.ps1</files>
  <action>
    Reescrever completamente scripts/setup.ps1. O script atual é frágil: esconde erros com `2>&1 | Out-Null`, não executa migrations, não valida health. O novo script deve:

    **Pré-requisitos e estrutura:**
    - Manter o header com Copyright e param([switch]$fresh) (modo fresh executa composer install do zero)
    - Extrair paths com Split-Path como já faz, mantendo root, dockerDir, backendDir, frontendDir
    - Definir função auxiliar `Test-Command` que executa um comando, mostra output, verifica exit code, e interrompe o script com `exit 1` em caso de erro

    **Fluxo de execução (numerado):**

    1. **Check Docker:**
       - `docker info` deve funcionar (sem `2>$null` — se falhar, mostrar erro claro)
       - Verificar se docker compose plugin está disponível: `docker compose version`

    2. **Check .env:**
       - Verificar se backend/.env existe. Se não existir, copiar de backend/.env.example
       - Verificar se APP_KEY está vazio — se estiver, avisar que será gerado no passo 5

    3. **Build Docker:**
       - `docker compose build php` — sem supressão de output, mostrar progresso. Se falhar, exit 1.
       - Se `$fresh`: `docker compose build --no-cache php` (força rebuild sem cache para pacotes novos)

    4. **Start containers:**
       - `docker compose up -d` — mostrar output, verificar exit code
       - **CRÍTICO**: Aguardar PostgreSQL ficar saudável:
         ```powershell
         Write-Host "Aguardando PostgreSQL ficar saudável..." -ForegroundColor Yellow
         docker compose wait postgres
         if (-not $?) { Write-Host "ERROR: PostgreSQL não ficou saudável" -ForegroundColor Red; exit 1 }
         ```

    5. **Backend setup (dentro do container):**
       - `docker compose exec -T php composer install --no-interaction --prefer-dist`
         (se `$fresh` ou vendor/ não existir; caso contrário, pular com aviso)
       - `docker compose exec -T php php artisan key:generate --force`
       - `docker compose exec -T php php artisan migrate --seed --force`
       - `docker compose exec -T php php artisan storage:link --force`
       
       Cada comando verifica exit code (`if (-not $?)`). Se qualquer um falhar, mostra erro e exit 1.

    6. **Frontend setup (host):**
       - `npm install` no diretório frontend (se node_modules/ não existir ou `$fresh`)
       - Mostrar output sem supressão

    7. **Validação final:**
       - `docker compose ps --format "table {{.Name}}\t{{.Status}}"` — listar status de todos os containers
       - Testar health endpoint: `curl -s http://localhost/up` — deve conter "OK"
       - Testar API health: `curl -s http://localhost/api/v1/health` — deve conter "ok"
       - Verificar se todos os 4 containers estão running

    8. **Summary:**
       - Mostrar tabela com URLs e credenciais de desenvolvimento:
         - Backend: http://localhost
         - Frontend: http://localhost:5173
         - PostgreSQL: localhost:5432 (user: labcontrol, password: labcontrol)
         - Redis: localhost:6379
         - Admin: admin@labcontrol.com / labcontrol
       - Mostrar duração total

    **Critérios de qualidade:**
    - NUNCA usar `2>&1 | Out-Null` em comandos críticos — o output deve ser visível para debug
    - Usar `Write-Host` com cores (Cyan para headers, Yellow para steps, Green para OK, Red para ERROR)
    - Cada passo numerado é auto-contido: mostra o que está fazendo, executa, valida, reporta
    - O script deve ser executável de qualquer diretório (já usa Split-Path)
    - Modo fresh (`-fresh`): executa composer install, rebuild sem cache, npm install fresh
  </action>
  <verify>
    <automated>
      # Validar sintaxe PowerShell
      $errors = $null; $null = [System.Management.Automation.PSParser]::Tokenize((Get-Content -Raw "scripts/setup.ps1"), [ref]$errors); if ($errors.Count -gt 0) { Write-Host "Syntax errors: $($errors.Count)" -ForegroundColor Red; $errors | ForEach-Object { Write-Host $_.Message } } else { Write-Host "Syntax OK" -ForegroundColor Green }
    </automated>
  </verify>
  <done>
    - setup.ps1 executa sem erros de sintaxe PowerShell
    - Todos os passos do fluxo estão implementados: check Docker → build → up → wait → composer → key:generate → migrate → seed → storage:link → npm install → validate
    - Validação final verifica health endpoints e status dos containers
    - Nenhum `2>&1 | Out-Null` suprimindo erros em passos críticos
    - Flag -fresh funcional para rebuild completo
  </done>
</task>

<task type="auto">
  <name>Task 2: Criar setup.sh para Linux/Mac</name>
  <files>scripts/setup.sh</files>
  <action>
    Criar scripts/setup.sh com funcionalidade equivalente ao setup.ps1. O script deve:

    **Cabeçalho:**
    ```bash
    #!/usr/bin/env bash
    set -euo pipefail
    ```
    - `set -e`: interrompe no primeiro erro
    - `set -u`: variáveis não definidas são erro
    - `set -o pipefail`: erros em pipes não são silenciados

    **Variáveis:**
    ```bash
    ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
    DOCKER_DIR="$ROOT_DIR/docker"
    BACKEND_DIR="$ROOT_DIR/backend"
    FRONTEND_DIR="$ROOT_DIR/frontend"
    FRESH="${1:-false}"  # $1 ou --fresh
    ```

    **Função de erro:**
    ```bash
    error() { echo -e "\e[31mERROR: $*\e[0m" >&2; exit 1; }
    info() { echo -e "\e[33m$*\e[0m"; }
    success() { echo -e "\e[32m$*\e[0m"; }
    header() { echo -e "\e[36m=== $* ===\e[0m"; }
    ```

    **Fluxo (mesmo do PowerShell):**
    1. Check Docker: `docker info >/dev/null 2>&1` ou error
    2. Check .env: copy from .env.example if missing
    3. Build: `docker compose build php` no diretório docker
    4. Start: `docker compose up -d` + `docker compose wait postgres`
    5. Composer: `docker compose exec -T php composer install --no-interaction --prefer-dist`
    6. Key:generate: `docker compose exec -T php php artisan key:generate --force`
    7. Migrate + seed: `docker compose exec -T php php artisan migrate --seed --force`
    8. Storage:link: `docker compose exec -T php php artisan storage:link --force`
    9. Frontend: `npm install` (se FRESH ou node_modules ausente)
    10. Validate: docker compose ps, curl health endpoints
    11. Summary: tabela de URLs e credenciais

    **Detalhes importantes:**
    - Usar `-T` no docker compose exec (desabilita TTY) para compatibilidade com CI/scripts
    - Usar `#!/usr/bin/env bash` em vez de `#!/bin/bash` para portabilidade
    - Usar `\e[31m` etc. para cores ANSI (compatível com Linux e Mac)
    - O script deve ser executável: `chmod +x scripts/setup.sh` (fazer isso via inline-comment ou instrução no verify)
    - Adicionar comentário no início: `# LabControl Setup Script | v1.0 | 2026-07-18`
    - Tornar o script executável após criar: `git update-index --chmod=+x scripts/setup.sh` ou `icacls` no Windows

    **Credenciais no summary:**
    ```
    Backend:    http://localhost
    Frontend:   http://localhost:5173
    PostgreSQL: localhost:5432 (labcontrol / labcontrol)
    Redis:      localhost:6379
    Admin:      admin@labcontrol.com / labcontrol
    ```
  </action>
  <verify>
    <automated>
      # Validar sintaxe bash
      bash -n scripts/setup.sh 2>&1; if ($?) { Write-Host "Syntax OK" -ForegroundColor Green }
    </automated>
  </verify>
  <done>
    - setup.sh criado com sintaxe bash válida
    - Usa `set -euo pipefail` para detecção robusta de erros
    - Fluxo idêntico ao setup.ps1 (check → build → up → wait → composer → key → migrate → seed → link → npm → validate)
    - Cores ANSI para feedback visual
    - Script marcado como executável
  </done>
</task>

<task type="auto">
  <name>Task 3: Executar e validar setup completo do zero</name>
  <files>Nenhum — execução de teste</files>
  <action>
    Executar o setup completo para validar que o fluxo integrado funciona. Esta task pressupõe que os containers não estão rodando (simula um ambiente limpo).

    **3a. Parar containers existentes:**
    ```powershell
    Set-Location docker
    docker compose down -v  # -v remove volumes para simular fresh start
    ```

    **3b. Remover link storage anterior (se existir):**
    ```powershell
    if (Test-Path "backend/public/storage") { Remove-Item "backend/public/storage" -Force }
    ```

    **3c. Limpar APP_KEY do .env:**
    ```powershell
    (Get-Content backend/.env) -replace '^APP_KEY=.*', 'APP_KEY=' | Set-Content backend/.env
    ```

    **3d. Executar setup.ps1 (modo fresh):**
    ```powershell
    Set-Location ..
    & .\scripts\setup.ps1 -fresh
    ```

    **3e. Validar resultados (verificação manual + automatizada):**
    - `docker compose ps --filter "status=running"` — 4 containers running
    - `curl http://localhost/up` — 200
    - `curl http://localhost/api/v1/health` — JSON com status "ok"
    - `docker compose exec -T php php artisan tinker --execute="echo App\Models\Role::count();"` — 6
    - `Test-Path backend/public/storage` — True
    - `Test-Path backend/vendor/autoload.php` — True (composer install funcionou)
    - `Test-Path frontend/node_modules` — True (npm install funcionou)

    **3f. Se encontrar erros:**
    - Debugar e corrigir no setup.ps1 ou setup.sh conforme necessário
    - Relatar no SUMMARY.md quaisquer ajustes feitos
  </action>
  <verify>
    <automated>
      Write-Host "Testando setup completo..."; Set-Location docker; docker compose ps --filter "status=running" 2>&1
    </automated>
  </verify>
  <done>
    - Setup completo executa sem erros do zero (containers parados, volumes limpos)
    - 4 containers running e saudáveis
    - Health endpoints respondendo (/up e /api/v1/health)
    - Migrations e seeders executados (6 papéis, admin user)
    - storage:link criado, vendor instalado, node_modules instalado
    - setup.ps1 e setup.sh sintaticamente válidos e funcionais
  </done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| Script execution (host) → Docker daemon | Script precisa de permissão Docker para executar comandos |
| npm install (host) | Pacotes npm baixados do registro público |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-01-09 | Tampering | npm install | medium | accept | Dependências frontend foram verificadas na Sprint 0; novas dependências passam por Package Legitimacy Audit antes de adicionar |
| T-01-10 | Denial of Service | docker compose down -v | medium | mitigate | Avisar no output do script que `-v` remove volumes; modo fresh intencional destrói dados de DB — usar sem -fresh para reter dados |
| T-01-11 | Information Disclosure | Credenciais no summary output | low | accept | Credenciais são de desenvolvimento (admin/labcontrol, DB labcontrol/labcontrol); produção terá credenciais diferentes |
| T-01-SC | Tampering | docker compose build php | high | mitigate | Apenas instala extensões oficiais via docker-php-ext-install e pecl; composer install baixa de packagist.org (laravel/sanctum é pacote oficial) — ver Research.md Package Legitimacy Audit |
</threat_model>

<verification>
1. `.\scripts\setup.ps1 -fresh` — executa sem erros, exit code 0
2. `docker compose ps --filter "status=running"` — 4 containers running
3. `curl -s http://localhost/up` — contém "OK" ou retorna 200
4. `curl -s http://localhost/api/v1/health` — JSON {status: "ok"}
5. `Test-Path backend/vendor/autoload.php` — True
6. `Test-Path frontend/node_modules` — True
7. `Test-Path backend/public/storage` — True
8. `bash -n scripts/setup.sh` — sem erros de sintaxe
</verification>

<success_criteria>
- [ ] setup.ps1 executa do zero sem erros
- [ ] Todos os 4 containers running ao final
- [ ] Health endpoints do Laravel respondem
- [ ] vendor/ instalado, node_modules instalado, storage link criado
- [ ] Papéis e admin user criados
- [ ] setup.sh criado com sintaxe válida e funcionalidade equivalente
- [ ] Ambos os scripts são executáveis de qualquer diretório
- [ ] Scripts são idempotentes (segunda execução não quebra)
</success_criteria>

<output>
Criar `.planning/phases/01-infraestrutura/03-PLAN-03-SUMMARY.md` quando concluído
</output>
