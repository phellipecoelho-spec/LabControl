#!/usr/bin/env bash
# LabControl Setup Script | v1.0 | 2026-07-18
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER_DIR="$ROOT_DIR/docker"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"
FRESH=false

if [[ "${1:-}" == "--fresh" ]]; then
    FRESH=true
fi

error() { echo -e "\e[31mERROR: $*\e[0m" >&2; exit 1; }
info() { echo -e "\e[33m$*\e[0m"; }
success() { echo -e "\e[32m$*\e[0m"; }
header() { echo -e "\e[36m--- $* ---\e[0m"; }

START=$(date +%s)
echo -e "\e[36m=== LabControl Setup ===\e[0m"

# 1. Check Docker
header "1/8: Verificando Docker"
docker info --format '{{.ServerVersion}}' >/dev/null 2>&1 || error "Docker nao esta rodando"
docker compose version >/dev/null 2>&1 || error "Docker Compose plugin nao encontrado"
success "Docker OK"

# 2. Check .env
header "2/8: Verificando .env"
if [ ! -f "$BACKEND_DIR/.env" ]; then
    if [ -f "$BACKEND_DIR/.env.example" ]; then
        cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
        info ".env criado a partir de .env.example"
    else
        error ".env.example nao encontrado"
    fi
fi
if grep -q "^APP_KEY=$" "$BACKEND_DIR/.env" 2>/dev/null; then
    info "APP_KEY vazio - sera gerado no passo 5"
fi

# 3. Build Docker
header "3/8: Build Docker"
cd "$DOCKER_DIR"
if [ "$FRESH" = true ]; then
    docker compose build --no-cache php || error "Build PHP falhou"
else
    docker compose build php || error "Build PHP falhou"
fi

# 4. Start containers
header "4/8: Iniciando containers"
docker compose up -d || error "docker compose up falhou"
info "Aguardando PostgreSQL..."
docker compose wait postgres || error "PostgreSQL nao ficou saudavel"
success "PostgreSQL saudavel"

# 5. Backend setup
header "5/8: Configurando backend"
if [ "$FRESH" = true ] || [ ! -f "$BACKEND_DIR/vendor/autoload.php" ]; then
    docker compose exec -T php composer install --no-interaction --prefer-dist || error "Composer install falhou"
else
    info "vendor/ ja existe, pulando composer install"
fi
docker compose exec -T php php artisan key:generate --force || error "key:generate falhou"
docker compose exec -T php php artisan migrate --seed --force || error "migrate --seed falhou"
docker compose exec -T php php artisan storage:link --force || error "storage:link falhou"

# 6. Frontend setup
header "6/8: Configurando frontend"
if [ "$FRESH" = true ] || [ ! -d "$FRONTEND_DIR/node_modules" ]; then
    cd "$FRONTEND_DIR"
    npm install || error "npm install falhou"
    cd "$DOCKER_DIR"
else
    info "node_modules/ ja existe, pulando npm install"
fi

# 7. Validacao final
header "7/8: Validando setup"
echo -e "\e[97mStatus dos containers:\e[0m"
docker compose ps --format "table {{.Name}}\t{{.Status}}"

UP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/up --max-time 10 2>/dev/null || echo "000")
if [ "$UP" = "200" ]; then
    success "Health /up: OK (200)"
else
    error "Health /up: FALHOU ($UP)"
fi

HEALTH=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health --max-time 10 2>/dev/null || echo "000")
if [ "$HEALTH" = "200" ]; then
    success "API /api/v1/health: OK (200)"
else
    error "API /api/v1/health: FALHOU ($HEALTH)"
fi

# 8. Summary
header "8/8: Summary"
DURATION=$(($(date +%s) - START))
echo -e "\e[32mSetup concluido em ${DURATION}s\e[0m"
echo -e "\e[97mURLs e credenciais de desenvolvimento:\e[0m"
echo -e "  \e[36mBackend:    http://localhost\e[0m"
echo -e "  \e[36mFrontend:   http://localhost:5173\e[0m"
echo -e "  \e[36mPostgreSQL: localhost:5432 (labcontrol / labcontrol)\e[0m"
echo -e "  \e[36mRedis:      localhost:6379\e[0m"
echo -e "  \e[36mAdmin:      admin@labcontrol.com / @dmin123\e[0m"
echo -e "\e[36m=== Setup completo! ===\e[0m"
