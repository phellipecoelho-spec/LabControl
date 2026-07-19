# Validation: Phase 1 — Infraestrutura

## Smoke Tests

### VT-01: Docker build
```powershell
docker compose build php
```
**Expected:** Build succeeds, exit code 0, image tagged `labcontrol-php`

### VT-02: Containers healthy
```powershell
docker compose up -d
docker compose ps
```
**Expected:** 4 containers (php, nginx, postgres, redis) all `healthy` or `Up`

### VT-03: Health endpoint
```powershell
curl -s http://localhost/api/v1/health
```
**Expected:** JSON `{"status":"ok","service":"labcontrol-api"}`

### VT-04: Laravel up
```powershell
curl -s -o NUL -w "%{http_code}" http://localhost/up
```
**Expected:** 200

### VT-05: Migrations
```powershell
docker compose exec php php artisan migrate --force
```
**Expected:** No errors, all migrations marked as `Y`

### VT-06: Seeders
```powershell
docker compose exec php php artisan db:seed --force
```
**Expected:** 6 roles created, 1 admin user created, ~40 permissions created

### VT-07: Full setup (from scratch)
```powershell
docker compose down -v
.\scripts\setup.ps1
```
**Expected:** Script exits with code 0, all VT-01 to VT-06 pass

### VT-08: Setup script idempotent
```powershell
.\scripts\setup.ps1
```
**Expected:** Script exits with code 0, no errors (migrate is no-op, seeders use firstOrCreate)
