# External Integrations

**Analysis Date:** 2026-07-19

## APIs & External Services

**Email Delivery:**
- **Amazon SES** - Email sending via AWS
  - SDK/Client: Native Laravel mail driver (`backend/config/mail.php`)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` (from `backend/config/services.php`)
- **Postmark** - Email sending via Postmark API
  - SDK/Client: Native Laravel mail driver
  - Auth: `POSTMARK_API_KEY` (from `backend/config/services.php`)
- **Resend** - Email sending via Resend API
  - SDK/Client: Native Laravel mail driver
  - Auth: `RESEND_API_KEY` (from `backend/config/services.php`)
- **Mailgun** - Email sending (driver available but not explicitly configured in services.php)
- **SMTP** - Generic SMTP email sending
  - Config: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` (`backend/config/mail.php`)

**Notifications:**
- **Slack** - Error notifications and log alerts
  - SDK/Client: Native Laravel Slack logging channel + notification channel
  - Auth: `SLACK_BOT_USER_OAUTH_TOKEN` (from `backend/config/services.php`)
  - Channel: `SLACK_BOT_USER_DEFAULT_CHANNEL`

**Logging:**
- **Papertrail** - Remote syslog log aggregation
  - SDK/Client: Monolog SyslogUdpHandler (configured in `backend/config/logging.php`)
  - Auth: `PAPERTRAIL_URL`, `PAPERTRAIL_PORT`
- **Slack Webhook** - Critical error alerts via Slack webhook
  - Config: `LOG_SLACK_WEBHOOK_URL` (`backend/config/logging.php`)

**Cloud Storage:**
- **Amazon S3** - File storage for certificates, photos, manuals, attachments, reports, exports
  - SDK/Client: Laravel Filesystem S3 driver (`backend/config/filesystems.php`)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
  - Used by: `backend/storage/certificates/`, `photos/`, `manuals/`, `attachments/`, `reports/`, `exports/`

**Queue Services:**
- **Amazon SQS** - Queue backend for job processing
  - SDK/Client: Native Laravel queue driver (`backend/config/queue.php`)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `SQS_PREFIX`, `SQS_QUEUE`
- **Beanstalkd** - Queue backend alternative
  - Config: `BEANSTALKD_QUEUE_HOST` (`backend/config/queue.php`)

**Cache Services:**
- **Amazon DynamoDB** - Cache store alternative
  - SDK/Client: Native Laravel cache driver (`backend/config/cache.php`)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`
- **Memcached** - Cache store alternative
  - Config: `MEMCACHED_HOST`, `MEMCACHED_PORT` (`backend/config/cache.php`)

## Data Storage

**Databases:**
- **PostgreSQL 17** — Primary database (production and Docker target)
  - Connection: `pgsql` driver (`backend/config/database.php`)
  - Host: `DB_HOST` (default `127.0.0.1`), Port: `DB_PORT` (default `5432`)
  - Init script: `docker/postgres/init/01-create-databases.sql` (creates `labcontrol_testing`, `labcontrol_staging`)
  - Client: Laravel Eloquent ORM
  - Extension: `uuid-ossp` enabled (`database/scripts/01-schema.sql`)
  - Docker: `postgres:17-alpine` with health check (`docker/docker-compose.yml`)
  - Docker credentials: `labcontrol` / `labcontrol` (dev only)

- **SQLite** — Default fallback / local development
  - File: `database/database.sqlite` (referenced in `backend/config/database.php`)
  - Client: Laravel Eloquent ORM

- **Redis** — Cache and queue backend
  - Image: `redis:7-alpine` (`docker/docker-compose.yml`)
  - Port: 6379
  - Client: `phpredis` extension (configured in `backend/config/database.php`)
  - Two logical databases: `default` (DB 0) and `cache` (DB 1)
  - Docker volume: `redis_data`

**File Storage:**
- **Local filesystem** — Default storage driver (`FILESYSTEM_DISK=local`)
  - Private: `backend/storage/app/private/`
  - Public: `backend/storage/app/public/` (symlinked at `public/storage`)
  - Dedicated directories for: certificates, photos, manuals, attachments, reports, exports (`backend/storage/`)
- **Amazon S3** — Configured as alternative cloud storage driver
  - Bucket: `AWS_BUCKET`, Region: `AWS_DEFAULT_REGION`

**Caching:**
- **Database cache** — Default cache store (`CACHE_STORE=database`)
- **Redis cache** — Configured as an alternative (`backend/config/cache.php`)
- **DynamoDB cache** — Configured as an alternative
- **Memcached cache** — Configured as an alternative

## Authentication & Identity

**Auth Provider:**
- **Laravel Sanctum** — Token-based API authentication (referenced in `docs/architecture/ARCHITECTURE.md`)
  - Implementation: Laravel's built-in Sanctum package (typically bundled with Laravel framework)
  - Guard: `session` driver with `eloquent` user provider (`backend/config/auth.php`)
  - Model: `App\Models\User` (`backend/app/Models/User.php`) extending `Authenticatable`
  - Password hashing: `bcrypt` via Laravel's `hashed` cast
  - Password reset: Token table `password_reset_tokens`

## Monitoring & Observability

**Error Tracking:**
- Not detected (no Sentry, Rollbar, or similar package configured)

**Logs:**
- **Laravel Log** — Default to `backend/storage/logs/laravel.log`
- **Stack driver** — Multiple channels support (single, daily, slack, papertrail, syslog, stderr)
- **Papertrail** — Remote syslog configured as a channel option
- **Laravel Pail** — Dev log viewer CLI tool (^1.2.5, `backend/composer.json` require-dev)

**Health Check:**
- Laravel built-in health route at `/up` (`backend/bootstrap/app.php`)

## CI/CD & Deployment

**Hosting:**
- **Docker Compose** — Local and production deployment (target platforms: DigitalOcean, Hostinger VPS, AWS, Hetzner, Oracle Cloud per `CONTEXT.md`)
- Deployment scripts: `scripts/setup.ps1` (development environment bootstrap)

**CI Pipeline:**
- No CI configuration files detected (no GitHub Actions, GitLab CI, Jenkins, etc.)

## Environment Configuration

**Required env vars (documented in config files):**

| Variable | Config File | Default |
|----------|-------------|---------|
| `APP_NAME` | `config/app.php` | `LabControl` |
| `APP_ENV` | `config/app.php` | `production` |
| `APP_DEBUG` | `config/app.php` | `false` |
| `APP_URL` | `config/app.php` | `http://localhost` |
| `APP_KEY` | `config/app.php` | *(required)* |
| `DB_CONNECTION` | `config/database.php` | `sqlite` |
| `DB_HOST` | `config/database.php` | `127.0.0.1` |
| `DB_PORT` | `config/database.php` | `5432` |
| `DB_DATABASE` | `config/database.php` | `labcontrol` |
| `DB_USERNAME` | `config/database.php` | `labcontrol` |
| `DB_PASSWORD` | `config/database.php` | *(secret)* |
| `REDIS_HOST` | `config/database.php` | `127.0.0.1` |
| `REDIS_PORT` | `config/database.php` | `6379` |
| `CACHE_STORE` | `config/cache.php` | `database` |
| `QUEUE_CONNECTION` | `config/queue.php` | `database` |
| `SESSION_DRIVER` | `config/session.php` | `database` |
| `FILESYSTEM_DISK` | `config/filesystems.php` | `local` |
| `MAIL_MAILER` | `config/mail.php` | `log` |
| `AWS_ACCESS_KEY_ID` | `config/services.php` | *(optional)* |
| `AWS_SECRET_ACCESS_KEY` | `config/services.php` | *(optional)* |
| `AWS_DEFAULT_REGION` | `config/services.php` | `us-east-1` |
| `AWS_BUCKET` | `config/filesystems.php` | *(optional)* |

**Secrets location:**
- Local `.env` file at `backend/.env` (gitignored)
- Production: Docker environment variables or cloud provider secrets manager

## Webhooks & Callbacks

**Incoming:**
- None configured (no webhook routes detected in `backend/routes/web.php` or `backend/routes/api.php`)

**Outgoing:**
- Slack log webhook (configured in `backend/config/logging.php` via `LOG_SLACK_WEBHOOK_URL`)
- Postmark webhook callbacks (expects Postmark to deliver, no custom handler)

---

*Integration audit: 2026-07-19*
