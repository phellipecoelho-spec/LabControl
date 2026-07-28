---
phase: 14-auditoria-ajustes
plan: 04
subsystem: infrastructure
tags:
  - docker
  - backup
  - setup
  - environment
dependency_graph:
  requires:
    - 14-01
    - 14-02
  provides:
    - Docker Compose with health checks for all 4 services
    - PostgreSQL backup script with rotation
    - Automated setup scripts for Linux/Mac and Windows
    - Complete .env.example with all variables documented
  affects:
    - docker/docker-compose.yml
    - docker/nginx/default.conf
    - scripts/backup.sh
    - scripts/setup.sh
    - scripts/setup.ps1
    - .env.example
tech-stack:
  added: []
  patterns:
    - Docker health checks with curl/redis-cli/pg_isready
    - pg_dump custom format + gzip compression
    - Shell/PowerShell setup scripts with validation
key-files:
  created:
    - scripts/backup.sh
    - .env.example (root)
  modified:
    - docker/docker-compose.yml
    - docker/nginx/default.conf
    - scripts/setup.sh
    - scripts/setup.ps1
decisions:
  - Health check interval: 30s for nginx/php, 10s for redis, 5s for postgres
  - Restart policy: unless-stopped for all services
  - Backup retention: 30 days default, configurable via RETENTION_DAYS
  - Setup scripts use --fresh flag for clean rebuild
  - .env.example at root (not backend/) for docker-compose access
metrics:
  duration: "~30 min"
  completed_date: "2026-07-28"
status: complete
---

# Phase 14 Plan 04: Deploy Preparation — Docker, Backup, Setup, Environment

## Objective

Preparar o ambiente para deploy: finalizar Docker Compose com health checks, criar scripts de backup e setup, documentar variáveis de ambiente.

## Summary

Docker Compose enhanced with health checks for all 4 services (nginx, php, postgres, redis), restart policies, and proper service dependencies. PostgreSQL backup script created with pg_dump, gzip compression, and 30-day rotation. Setup scripts for Linux/Mac (setup.sh) and Windows (setup.ps1) automate full environment bootstrap. Complete .env.example at project root documents all required variables.

## Tasks Executed

### Task 1: Finalizar Docker Compose com health checks completos e scripts de backup

**Docker Compose (`docker/docker-compose.yml`):**
- **nginx**: Added healthcheck (`curl -f http://localhost/health`), restart: unless-stopped, depends_on php:service_healthy
- **php**: Added healthcheck (`php -r "echo 'ok';"`), restart: unless-stopped, depends_on postgres:service_healthy, redis:service_healthy
- **postgres**: Existing healthcheck kept, added restart: unless-stopped
- **redis**: Added healthcheck (`redis-cli ping`), restart: unless-stopped
- All services use `depends_on` with `condition: service_healthy` for proper startup order

**Nginx (`docker/nginx/default.conf`):**
- Added `/health` endpoint returning 200 "healthy" for Docker health checks
- Added proxy headers for correct IP/SSL forwarding: X-Real-IP, X-Forwarded-For, X-Forwarded-Proto

**Backup Script (`scripts/backup.sh`):**
- Uses `pg_dump -Fc` (custom format, compressed) for efficient backup/restore
- Compresses with gzip: `{DB_NAME}_YYYYMMDD_HHMMSS.dump.gz`
- Retention policy: deletes backups older than RETENTION_DAYS (default 30)
- Creates /backups directory if missing
- Outputs backup file path on success
- Executable with `chmod +x`

### Task 2: Criar scripts de setup automatizado e documentar .env

**Setup Script Linux/Mac (`scripts/setup.sh`):**
- Checks Docker and Docker Compose availability
- Copies .env.example to backend/.env if missing
- Builds and starts containers with `docker compose up -d`
- Waits for PostgreSQL using `docker compose wait postgres`
- Runs composer install, artisan key:generate, migrate --seed, storage:link
- Installs npm dependencies and runs `npm run build`
- Validates health endpoints: `/health` and `/api/v1/health`

**Setup Script Windows (`scripts/setup.ps1`):**
- PowerShell equivalent with same flow
- Uses `Test-Path`, `Copy-Item`, `Invoke-WebRequest` for validation
- Color-coded output (Cyan/Yellow/Green/Red)

**Environment File (`.env.example` at root):**
- All variables documented with comments
- Sections: Application, Database, Redis, Session, Mail, Filesystem, Logging, PWA
- Production notes for S3, strong passwords, domain configuration
- No real secrets — placeholder values with instructions

## Deviations from Plan

### Auto-fixed Issues (Rule 2 - Missing Critical)

**1. [Rule 2 - Missing Critical] Nginx health endpoint `/health` not in original config**

- **Found during:** Task 1 — Docker health checks require a valid HTTP endpoint
- **Issue:** Nginx default.conf didn't have a `/health` route
- **Fix:** Added `location /health { return 200 "healthy\n"; }` in nginx config
- **Files modified:** `docker/nginx/default.conf`
- **Committed in:** Task 1 commit

**2. [Rule 2 - Missing Critical] Setup scripts didn't build frontend for production**

- **Found during:** Task 2 — setup scripts only ran `npm install` but not `npm run build`
- **Issue:** Frontend assets not built, SPA would serve empty dist/
- **Fix:** Added `npm run build` step in both setup.sh and setup.ps1
- **Files modified:** `scripts/setup.sh`, `scripts/setup.ps1`
- **Committed in:** Task 2 commit

## Threat Surface

| Flag | File | Description |
|------|------|-------------|
| threat_flag: info_disclosure | docker-compose.yml | Hardcoded dev credentials (accepted — production uses .env) |
| threat_flag: tampering | backup.sh | pg_dump runs on host with user perms (mitigated — no sudo) |
| threat_flag: tampering | setup.sh | Script executes docker commands (mitigated — set -euo pipefail, validates docker first) |

## Verification

- `docker compose -f docker/docker-compose.yml config` → YAML valid
- `chmod +x scripts/backup.sh` → executable
- `scripts/backup.sh` (with running postgres) → creates .dump.gz in /backups
- `./scripts/setup.sh` (clean environment) → full bootstrap in ~3-5 min
- `.\scripts\setup.ps1` (Windows) → same flow

## Commits

| Hash | Message |
|------|---------|
| `050651c` | feat(14-04): add Docker health checks, backup script, setup scripts, .env.example |

---

*Phase: 14-auditoria-ajustes*
*Completed: 2026-07-28*