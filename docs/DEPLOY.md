# LabControl — Deploy Guide

## Índice

1. [Pré-requisitos](#pré-requisitos)
2. [Setup Rápido (Dev)](#setup-rápido-dev)
3. [Deploy em Produção](#deploy-em-produção)
4. [Configuração SSL](#configuração-ssl)
5. [Backup](#backup)
6. [Variáveis de Ambiente](#variáveis-de-ambiente)
7. [Manutenção](#manutenção)

## Pré-requisitos

- **Docker** 24+ e **Docker Compose** v2+
- **Git**
- **Domínio** (para produção) configurado com DNS apontando para o servidor
- Portas 80 e 443 liberadas no firewall

## Setup Rápido (Dev)

```bash
git clone <repo-url> labcontrol
cd labcontrol
cp .env.example backend/.env
# Editar backend/.env se necessário
docker compose -f docker/docker-compose.yml up -d
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
docker compose exec php npm install && npm run build
```

Acessar: http://localhost

## Deploy em Produção

### 1. Preparar servidor

```bash
# Ubuntu/Debian
apt update && apt install -y docker.io docker-compose-v2 nginx certbot
systemctl enable docker
```

### 2. Clonar e configurar

```bash
git clone <repo-url> /opt/labcontrol
cd /opt/labcontrol
cp .env.example backend/.env
```

### 3. Configurar .env para produção

Editar `backend/.env`:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com
DB_PASSWORD=senha-forte-aqui
SESSION_DOMAIN=seu-dominio.com
SANCTUM_STATEFUL_DOMAINS=seu-dominio.com
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=senha-app
```

### 4. Build e iniciar

```bash
docker compose -f docker/docker-compose.yml build
docker compose -f docker/docker-compose.yml up -d
docker compose exec php php artisan key:generate --force
docker compose exec php php artisan migrate --force
docker compose exec php php artisan db:seed --force
docker compose exec php php artisan storage:link
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php npm install && npm run build
```

### 5. Configurar Nginx reverso (SSL)

```nginx
# /etc/nginx/sites-available/labcontrol
server {
    listen 80;
    server_name seu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com;

    ssl_certificate /etc/letsencrypt/live/seu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seu-dominio.com/privkey.pem;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Ativar:
```bash
ln -s /etc/nginx/sites-available/labcontrol /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### 6. SSL com Let's Encrypt

```bash
certbot --nginx -d seu-dominio.com
```

## Backup

```bash
# Backup manual
docker compose exec postgres pg_dump -U labcontrol labcontrol > backup_$(date +%Y%m%d).sql

# Usar script automatizado
./scripts/backup.sh

# Restore
docker compose exec -T postgres psql -U labcontrol labcontrol < backup.sql
```

Para backup automático, adicionar cron:
```cron
0 2 * * * /opt/labcontrol/scripts/backup.sh
```

## Variáveis de Ambiente

Ver [.env.example](../.env.example) na raiz do projeto para documentação completa.

## Manutenção

```bash
# Atualizar código
git pull
docker compose exec php composer install --no-dev
docker compose exec php php artisan migrate --force
docker compose exec php npm install && npm run build

# Logs
docker compose logs -f php
docker compose logs -f nginx

# Reiniciar serviço
docker compose restart php

# Health check
curl http://localhost/health
```