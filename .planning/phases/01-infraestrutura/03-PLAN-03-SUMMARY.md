---
phase: 01-infraestrutura
plan: 03
type: execute
wave: 3
depends_on:
  - 02
status: completed
completed_at: 2026-07-19T11:58:00Z
---

<summary>
**Plano 03-03: Script de Setup (PowerShell + Bash)**

### Objetivo Alcançado
Scripts `setup.ps1` (PowerShell) e `setup.sh` (Bash) já existiam e são robustos, com health checks, validação de erros, suporte a modo `--fresh`, e sumário final com URLs e credenciais.

### Arquivos Verificados
- `scripts/setup.ps1` — PowerShell com funções `Test-Command`, health checks `/up` e `/api/v1/health`, validação de containers, modo `-fresh`
- `scripts/setup.sh` — Bash com `set -euo pipefail`, funções de erro/info/success, health checks via `curl`, validação de containers, modo `--fresh`

### Funcionalidades Verificadas
✅ Verificação de Docker e Docker Compose  
✅ Criação de `.env` a partir de `.env.example` se não existir  
✅ Build da imagem PHP (com `--no-cache` no modo fresh)  
✅ Start dos containers (nginx, php, postgres, redis) com `docker compose wait postgres`  
✅ Composer install (pula se vendor/ existe, exceto modo fresh)  
✅ `php artisan key:generate --force`  
✅ `php artisan migrate --seed --force`  
✅ `php artisan storage:link --force`  
✅ `npm install` (pula se node_modules/ existe, exceto modo fresh)  
✅ Validação final: status dos containers, health check `/up`, health check `/api/v1/health`  
✅ Sumário final com URLs, credenciais e tempo de execução

### Verificações Realizadas
✅ Health check `/up` → 200 OK  
✅ Health check `/api/v1/health` → 200 OK com JSON `{"status":"ok","timestamp","version"}`  
✅ CORS headers presentes: `Access-Control-Allow-Origin: http://localhost:5173`, `Access-Control-Allow-Credentials: true`  
✅ Extensão Redis PHP instalada e habilitada  
✅ 4 containers rodando e saudáveis (nginx, php, postgres, redis)  
✅ Banco populado: 6 roles, 31 permissions, 1 admin user

### Bloqueios Resolvidos
- Scripts já existiam e atendem todos os requisitos do plano
- Validação de erro explícita sem supressão (`2>&1 | Out-Null` não usado em passos críticos)
- Modo idempotente: scripts podem ser executados múltiplas vezes sem quebrar

### Próximos Passos
Fase 1 completa. Avançar para Fase 2 (módulos do sistema: Equipamentos, Estoque, etc.).