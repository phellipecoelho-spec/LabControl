# LabControl Setup Script
param(
    [switch]$fresh
)

$root = Split-Path -Parent $PSScriptRoot
$dockerDir = Join-Path $root "docker"
$backendDir = Join-Path $root "backend"
$frontendDir = Join-Path $root "frontend"

Write-Host "=== LabControl Setup ===" -ForegroundColor Cyan
Write-Host ""

# 1. Check Docker
Write-Host "[1/4] Checking Docker..." -ForegroundColor Yellow
docker info --format "{{.ServerVersion}}" 2>$null
if (-not $?) {
    Write-Host "ERROR: Docker is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}
Write-Host "Docker OK" -ForegroundColor Green

# 2. Backend setup
Write-Host "[2/4] Setting up backend..." -ForegroundColor Yellow
Set-Location $dockerDir
docker compose build php 2>&1 | Out-Null
if ($fresh) {
    docker compose run --rm php composer install --no-interaction 2>&1 | Out-Null
}
docker compose run --rm php php artisan key:generate 2>&1 | Out-Null
Write-Host "Backend OK" -ForegroundColor Green

# 3. Start containers
Write-Host "[3/4] Starting containers..." -ForegroundColor Yellow
docker compose up -d 2>&1 | Out-Null
Write-Host "Containers OK" -ForegroundColor Green

# 4. Frontend setup
Write-Host "[4/4] Setting up frontend..." -ForegroundColor Yellow
Set-Location $frontendDir
npm install 2>&1 | Out-Null
Write-Host "Frontend OK" -ForegroundColor Green

Write-Host ""
Write-Host "=== Setup complete! ===" -ForegroundColor Cyan
Write-Host "Backend: http://localhost:80"
Write-Host "Frontend: http://localhost:5173"
Write-Host "PostgreSQL: localhost:5432"
Write-Host "Redis: localhost:6379"
