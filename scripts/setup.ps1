# LabControl Setup Script | v1.0 | 2026-07-18
param([switch]$fresh)

$root = Split-Path -Parent $PSScriptRoot
$dockerDir = Join-Path $root "docker"
$backendDir = Join-Path $root "backend"
$frontendDir = Join-Path $root "frontend"

function Test-Command {
    param([scriptblock]$Block, [string]$Label)
    Write-Host "[$Label]..." -ForegroundColor Yellow
    try {
        & $Block
        if (-not $?) { throw "Command failed" }
        Write-Host "$Label OK" -ForegroundColor Green
    } catch {
        Write-Host "ERROR: $Label falhou - $_" -ForegroundColor Red
        exit 1
    }
}

$start = Get-Date
Write-Host "=== LabControl Setup ===" -ForegroundColor Cyan

# 1. Check Docker
Write-Host "`n--- 1/8: Verificando Docker ---" -ForegroundColor Cyan
Test-Command -Label "Docker info" -Block { docker info --format "{{.ServerVersion}}" }
Test-Command -Label "Docker Compose" -Block { docker compose version }

# 2. Check .env
Write-Host "`n--- 2/8: Verificando .env ---" -ForegroundColor Cyan
$envFile = Join-Path $backendDir ".env"
if (-not (Test-Path $envFile)) {
    $example = Join-Path $backendDir ".env.example"
    if (Test-Path $example) {
        Copy-Item $example $envFile
        Write-Host ".env criado a partir de .env.example" -ForegroundColor Green
    } else {
        Write-Host "ERROR: .env.example não encontrado em $example" -ForegroundColor Red
        exit 1
    }
}
$appKey = Select-String -Path $envFile -Pattern "^APP_KEY=(.+)$"
if (-not $appKey -or [string]::IsNullOrWhiteSpace($appKey.Matches.Groups[1].Value)) {
    Write-Host "APP_KEY vazio - será gerado no passo 5" -ForegroundColor Yellow
}

# 3. Build Docker
Write-Host "`n--- 3/8: Build Docker ---" -ForegroundColor Cyan
Set-Location $dockerDir
if ($fresh) {
    Test-Command -Label "Build PHP (no-cache)" -Block { docker compose build --no-cache php }
} else {
    Test-Command -Label "Build PHP" -Block { docker compose build php }
}

# 4. Start containers
Write-Host "`n--- 4/8: Iniciando containers ---" -ForegroundColor Cyan
Test-Command -Label "docker compose up" -Block { docker compose up -d }
Write-Host "Aguardando PostgreSQL ficar saudável..." -ForegroundColor Yellow
docker compose wait postgres
if (-not $?) {
    Write-Host "ERROR: PostgreSQL não ficou saudável" -ForegroundColor Red
    exit 1
}
Write-Host "PostgreSQL saudável" -ForegroundColor Green

# 5. Backend setup
Write-Host "`n--- 5/8: Configurando backend ---" -ForegroundColor Cyan
$vendorDir = Join-Path $backendDir "vendor"
if ($fresh -or -not (Test-Path (Join-Path $vendorDir "autoload.php"))) {
    Test-Command -Label "Composer install" -Block { docker compose exec -T php composer install --no-interaction --prefer-dist }
} else {
    Write-Host "vendor/ já existe, pulando composer install" -ForegroundColor Yellow
}
Test-Command -Label "Key generate" -Block { docker compose exec -T php php artisan key:generate --force }
Test-Command -Label "Migrate & seed" -Block { docker compose exec -T php php artisan migrate --seed --force }
Test-Command -Label "Storage link" -Block { docker compose exec -T php php artisan storage:link --force }

# 6. Frontend setup
Write-Host "`n--- 6/8: Configurando frontend ---" -ForegroundColor Cyan
$nodeModules = Join-Path $frontendDir "node_modules"
if ($fresh -or -not (Test-Path $nodeModules)) {
    Set-Location $frontendDir
    Test-Command -Label "npm install" -Block { npm install }
    Set-Location $dockerDir
} else {
    Write-Host "node_modules/ já existe, pulando npm install" -ForegroundColor Yellow
}

# 7. Validação final
Write-Host "`n--- 7/8: Validando setup ---" -ForegroundColor Cyan
Write-Host "Status dos containers:" -ForegroundColor White
docker compose ps --format "table {{.Name}}\t{{.Status}}"

$upResult = $null
try { $upResult = Invoke-WebRequest -Uri "http://localhost/up" -UseBasicParsing -TimeoutSec 10 } catch {}
if ($upResult -and $upResult.StatusCode -eq 200) {
    Write-Host "Health /up: OK (200)" -ForegroundColor Green
} else {
    Write-Host "Health /up: FALHOU" -ForegroundColor Red
    exit 1
}

$healthResult = $null
try { $healthResult = Invoke-WebRequest -Uri "http://localhost/api/v1/health" -UseBasicParsing -TimeoutSec 10 } catch {}
if ($healthResult -and $healthResult.StatusCode -eq 200) {
    Write-Host "API /api/v1/health: OK (200)" -ForegroundColor Green
} else {
    Write-Host "API /api/v1/health: FALHOU" -ForegroundColor Red
    exit 1
}

# 8. Summary
Write-Host "`n--- 8/8: Summary ---" -ForegroundColor Cyan
$duration = (Get-Date) - $start
Write-Host "Setup concluído em $($duration.Minutes)m $($duration.Seconds)s" -ForegroundColor Green
Write-Host "`nURLs e credenciais de desenvolvimento:" -ForegroundColor White
Write-Host "  Backend:    http://localhost" -ForegroundColor Cyan
Write-Host "  Frontend:   http://localhost:5173" -ForegroundColor Cyan
Write-Host "  PostgreSQL: localhost:5432 (labcontrol / labcontrol)" -ForegroundColor Cyan
Write-Host "  Redis:      localhost:6379" -ForegroundColor Cyan
Write-Host "  Admin:      admin@labcontrol.com / @dmin123" -ForegroundColor Cyan
Write-Host "`n=== Setup completo! ===" -ForegroundColor Cyan
