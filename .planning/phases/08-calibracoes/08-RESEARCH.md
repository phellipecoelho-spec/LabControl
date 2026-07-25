# Phase 8: Calibrações — Research

**Researched:** 2026-07-25
**Domain:** Gerenciamento de calibrações periódicas de equipamentos laboratoriais
**Confidence:** HIGH — all patterns verified against live Phase 7 (loans) and Phase 5 (equipment photos) codebase

## Summary

O módulo de Calibrações gerencia eventos periódicos de calibração de equipamentos, com suporte a certificados anexados, alertas de vencimento com 30 dias de antecedência e consulta de histórico por equipamento. Este módulo segue os mesmos padrões arquiteturais estabelecidos nas fases anteriores (Empréstimos, Estoque, Equipamentos): Laravel backend com API REST, Vue 3 + PrimeVue frontend, Pinia stores, composição por módulo.

A principal diferença entre Calibração (Phase 8) e Aferição (Phase 9) é que calibrações são eventos programados com periodicidade (ex: "a cada 6 meses"), geram certificado e são realizadas por laboratório externo ou interno. Aferições são verificações diárias/semanais pelo operador, sem certificado, apenas registro.

Três problemas técnicos merecem atenção especial: (1) o cálculo de `next_due_at` no momento da conclusão da calibração — usando `Carbon::addMonths()`, `addDays()`, ou `addHours()` conforme a unidade; (2) a detecção de vencimento — não é um status direto, mas sim uma condição: última calibração `completed` com `next_due_at < now()`; (3) o upload de certificados — deve seguir o padrão de `EquipmentPhotoService` mas com suporte a PDF além de imagens.

**Primary recommendation:** Implementar seguindo exatamente o padrão do módulo de Empréstimos (LoanController, LoanService, LoanResource, ListPage + DetailPage + Dialogs), substituindo a lógica de negócio de calibrações. Para upload de certificados, adaptar o EquipmentPhotoService com suporte a PDF.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Calibração pertence a UM equipamento (FK `equipment_id`). Relacionamento 1:N (Equipment → Calibrations)
- **D-02:** Modelagem de periodicidade **simples** — cada calibração registra `interval_value` (ex: 6) e `interval_unit` (months, days, hours). A próxima data é calculada no momento da conclusão: `next_due_at = completed_at + interval`. Sem tabela de schedules
- **D-03:** Status da calibração: `scheduled` (agendada/pendente), `completed` (concluída), `cancelled` (cancelada). Um equipamento com calibração vencida = última `completed` com `next_due_at < now()`
- **D-04:** Campos da tabela `calibrations`: equipment_id, status, scheduled_date, completed_at (nullable), next_due_at (nullable), interval_value (int), interval_unit (string: months/days/hours), responsible, laboratory, certificate_number (nullable), notes, created_by (FK users)
- **D-05:** Suporte a partes/componentes — campo opcional `part_name` na calibração
- **D-06:** Diferenciação Calibração vs Aferição: Calibração tem periodicidade + certificado + laboratório + custo associado
- **D-07:** Modelagem 1:N direta — tabela `calibration_certificates` com FK `calibration_id`
- **D-08:** Campos `calibration_certificates`: calibration_id, filename, filepath, mime_type, size_bytes, certificate_number, issuer, issued_at, validity_start, validity_end, notes
- **D-09:** Armazenamento em `storage/app/public/calibrations/certificates/`
- **D-10:** Upload via service similar a EquipmentPhotoService, com validação de tipo (PDF, imagens)
- **D-11:** Alerta único com **30 dias de lead time** — comando scheduled diário que verifica `next_due_at`
- **D-12:** Mesmo padrão do comando `CheckOverdueLoans` (Phase 7)
- **D-13:** Sem notificação por email nesta fase
- **D-14:** Histórico por equipamento via **página de listagem com filtro** — não como aba no DetailPage do equipamento
- **D-15:** ListPage com DataTable e filtros: por equipamento (select), período (date range), status, laboratório
- **D-16:** Colunas da lista: Equipamento, Parte, Data Agendada, Data Conclusão, Próxima Data, Laboratório, Status, Ações
- **D-17:** Padrão ListPage + criação por Dialog modal (mesmo padrão Empréstimos)
- **D-18:** DetailPage com abas: Dados da Calibração, Certificados, Timeline
- **D-19:** Dialog de criação: equipamento (select), parte (texto), data agendada, intervalo (valor + unidade), responsável, laboratório, observações
- **D-20:** Ao concluir calibração: dialog separado para preencher completed_at, next_due_at, certificate_number
- **D-21:** Permissões: `calibracoes.view`, `calibracoes.create`, `calibracoes.edit`, `calibracoes.concluir`, `calibracoes.cancel`
- **D-22:** Sidebar: categoria "Operações" → "Calibrações" (ícone pi-verified, permissão calibracoes.view)
- **D-23:** Rotas: `/calibrations` (index), `/calibrations/{id}` (show)

### the agent's Discretion
- Nomes específicos de rotas, controllers, services seguindo convenções dos módulos existentes
- Ordem de implementação (backend DB → backend CRUD → frontend CRUD + notificação)
- Índices do banco além dos obrigatórios (FKs)
- Layout exato de cada aba da DetailPage (campos, ordem, grid)
- Estratégia de validação (campos obrigatórios, transições de status)
- Template da notificação in-app (texto, prioridade, link)
- Ícone e label exatos para os botões de ação
- Quantidade/limite de certificados por calibração
- Formato de exibição da periodicidade na UI
- Cálculo de `next_due_at` considerando dias úteis ou corridos

### Deferred Ideas (OUT OF SCOPE)
- Notificação por email de vencimento
- Calendário visual mensal de calibrações
- Gatilho por horas de uso
- Workflow de aprovação de certificados
- Relatórios de calibrações
- Tabela parametrizada de partes/componentes por equipamento
- Integração com laboratórios externos via API

</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| CAL-01 | Usuário pode gerenciar agenda de calibrações periódicas | CRUD completo (ListPage + Create/Edit Dialog + DetailPage) com campos de periodicidade (interval_value + interval_unit), status (scheduled/completed/cancelled), laboratório, responsável |
| CAL-02 | Usuário pode anexar certificados de calibração | CalibrationCertificateService (adaptado de EquipmentPhotoService), upload de PDF + imagens, download, listagem na aba Certificados da DetailPage |
| CAL-03 | Sistema alerta quando calibração está vencida | CheckCalibrationDue command (adaptado de CheckOverdueLoans), daily schedule, notificações in-app para admin/supervisor com 30 dias de lead time |
| CAL-04 | Usuário pode consultar histórico de calibrações por equipamento | ListPage com filtro por equipamento (Select), período (DatePicker), status, laboratório; DetailPage com Timeline (LogsActivity) |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Gerenciamento de calibrações (CRUD) | API / Backend | — | Regras de negócio (cálculo next_due_at, transições de status) no backend; Frontend apenas exibe/formulários |
| Upload/download de certificados | API / Backend | Storage (FS) | Service de upload com validação (tipo/tamanho), Storage disk `public` com symlink |
| Alerta de vencimento | Backend (CLI) | Database | Scheduled command roda daily no servidor; consulta `next_due_at` e cria notificações na tabela `notifications` |
| Consulta de histórico | Browser / Client | API / Backend | Filtros no frontend; API retorna coleção paginada com scopes/filtros |
| Exibição de periodicidade | Browser / Client | — | Formatação de `interval_value` + `interval_unit` para display (ex: "6 meses") |

## Standard Stack

### Core (same as existing phases, verified)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel | ^13.8 | Backend API framework | Project standard |
| Vue 3 | ^3.5.40 | Frontend SPA framework | Project standard |
| PrimeVue | ^5.0.0 | UI component library | Project standard |
| Pinia | ^4.0.2 | State management | Project standard |
| Vue Router | ^5.2.0 | Client-side routing | Project standard |

### Supporting (Phase 8 specific)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Carbon | built-in Laravel | Date arithmetic for next_due_at calculation | All date math (addMonths, addDays, addHours) |
| `Storage::disk('public')` | Laravel | Certificate file storage | Upload/download certificates |

**No new packages required.** Everything needed ships with Laravel/Vue/PrimeVue.

## Package Legitimacy Audit

> **No external packages to install.** This phase uses only packages already in the project: `laravel/framework` (includes Carbon, Storage), Vue 3, PrimeVue 5, Pinia, Vue Router. All verified in `backend/composer.json` and `frontend/package.json`.

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                           Bro wser (Vue SPA)                        │
│                                                                     │
│  /calibrations          /calibrations/{id}                          │
│  ┌─────────────────┐    ┌────────────────────────────────┐         │
│  │ CalibrationsList │    │ CalibrationDetailPage          │         │
│  │   Page           │    │  ┌─────────────────────────┐  │         │
│  │ ┌─────────────┐  │    │  │ Dados da Calibração    │  │         │
│  │ │DataTable    │  │    │  │ (InfoTab)              │  │         │
│  │ │+ Filtros:   │  │    │  ├─────────────────────────┤  │         │
│  │ │ equipamento │  │    │  │ Certificados           │  │         │
│  │ │ período     │  │    │  │ (CertificateListTab)   │  │         │
│  │ │ status      │  │    │  ├─────────────────────────┤  │         │
│  │ │ laboratório │  │    │  │ Timeline (LogsActivity)│  │         │
│  │ └─────────────┘  │    │  └─────────────────────────┘  │         │
│  │ ┌─────────────┐  │    └────────────────────────────────┘         │
│  │ │Create Dialog│  │                                              │
│  │ │Complete Dlg │  │                                              │
│  └─────────────────┘                                               │
│           │                                                         │
│           │  Axios HTTP REST API                                    │
└───────────┼─────────────────────────────────────────────────────────┘
            │
┌───────────▼─────────────────────────────────────────────────────────┐
│                      Laravel Backend (API)                          │
│                                                                     │
│  POST /api/v1/v1/calibrations                        GET /api/v1/...│
│  │                                                      ▲           │
│  ▼                                                      │           │
│  ┌──────────────────┐      ┌─────────────────────┐     │           │
│  │ CalibrationCtrl  │─────▶│ CalibrationService  │─────┘           │
│  │ (thin)           │      │ (business logic)    │                 │
│  └──────────────────┘      └─────────────────────┘                 │
│         │                          │                                │
│         │ Form Request             │ DB::transaction                │
│         ▼                          ▼                                │
│  ┌──────────────────┐      ┌─────────────────────┐                 │
│  │ Validation       │      │ Calibration Model   │                 │
│  │ (FormRequest)    │      │ (HasUuids, SL, LA)  │                 │
│  └──────────────────┘      └─────────────────────┘                 │
│                                     │                                │
│  ┌──────────────────┐              │ hasMany                        │
│  │ CalResultResource│              ▼                                │
│  │ CalResultCollec  │     ┌─────────────────────┐                   │
│  └──────────────────┘     │ CalibrationCert     │                   │
│                            │ (photo-like model)  │                   │
│                            └─────────────────────┘                   │
│                                     │                                │
│                                     ▼                                │
│                            ┌─────────────────────┐                   │
│                            │ Storage: public/    │                   │
│                            │ calibrations/       │                   │
│                            │ certificates/       │                   │
│                            └─────────────────────┘                   │
│                                                                     │
│  ┌──────────────────────────────────────────────┐                   │
│  │ Kernel Schedule (daily)                      │                   │
│  │  ┌────────────────────────────────────────┐  │                   │
│  │  │ CheckCalibrationDue                    │  │                   │
│  │  │  → finds completed calibrations where  │  │                   │
│  │  │    next_due_at BETWEEN now() AND       │  │                   │
│  │  │    now()+30days                        │  │                   │
│  │  │  → creates in-app notifications for    │  │                   │
│  │  │    admin and supervisor users          │  │                   │
│  │  └────────────────────────────────────────┘  │                   │
│  └──────────────────────────────────────────────┘                   │
└─────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         PostgreSQL                                   │
│                                                                     │
│  calibrations: id, equipment_id (FK), part_name?, status,          │
│    scheduled_date, completed_at?, next_due_at?,                     │
│    interval_value, interval_unit, responsible, laboratory,          │
│    certificate_number?, notes, created_by (FK users),              │
│    created_at, updated_at, deleted_at, deleted_by                   │
│                                                                     │
│  calibration_certificates: id, calibration_id (FK), filename,      │
│    filepath, mime_type, size_bytes, certificate_number?,           │
│    issuer?, issued_at?, validity_start?, validity_end?,            │
│    notes?, created_at, updated_at                                   │
│                                                                     │
│  notifications: (existing table from Phase 7)                      │
│    storing CalibrationDue notifications                             │
└─────────────────────────────────────────────────────────────────────┘
```

### Database Schema

**Migration compound** (`2026_07_25_000001_create_calibrations_tables.php`) — same pattern as `2026_07_21_000001_create_loans_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table: calibrations
        Schema::create('calibrations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('equipment_id')->constrained('equipments');
            $table->string('part_name', 255)->nullable();                      // D-05: optional part/component name
            $table->string('status', 20)->default('scheduled');                 // D-03: scheduled | completed | cancelled
            $table->date('scheduled_date');                                     // planned date for the calibration
            $table->timestamp('completed_at')->nullable();                      // when completed (D-02)
            $table->timestamp('next_due_at')->nullable();                       // computed: completed_at + interval (D-02)
            $table->integer('interval_value');                                   // e.g. 6, 30, 1000 (D-02)
            $table->string('interval_unit', 10);                                // months, days, hours (D-02)
            $table->string('responsible', 255)->nullable();                     // person responsible
            $table->string('laboratory', 255)->nullable();                      // calibration lab
            $table->string('certificate_number', 100)->nullable();              // calibration certificate number
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for frequent queries
            $table->index(['equipment_id']);
            $table->index(['status']);
            $table->index(['scheduled_date']);
            $table->index(['next_due_at']);
            $table->index(['laboratory']);
            $table->index(['status', 'next_due_at']);          // composite: due-check query
            $table->index(['equipment_id', 'status']);          // composite: equipment history filtered
        });

        // Table: calibration_certificates (D-07, D-08)
        Schema::create('calibration_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('calibration_id')->constrained('calibrations')->onDelete('cascade');
            $table->string('filename', 255);                                     // original filename
            $table->string('filepath', 255);                                     // storage path
            $table->string('mime_type', 50);
            $table->integer('size_bytes');
            $table->string('certificate_number', 100)->nullable();
            $table->string('issuer', 255)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('validity_start')->nullable();
            $table->date('validity_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['calibration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_certificates');
        Schema::dropIfExists('calibrations');
    }
};
```

**Key schema decisions:**
- `interval_unit` as string (`months`, `days`, `hours`) — NOT an enum. The agent's discretion allows validation via `in:months,days,hours` in FormRequest.
- `interval_value` as integer — sufficient for typical intervals (1-60 months, 1-365 days, 1-10000 hours).
- `scheduled_date` as date (not datetime) — consistent with existing `acquisition_date`, `warranty_end` in Equipment.
- `completed_at`, `next_due_at` as nullable timestamps — filled only when status transitions to `completed`.
- `calibration_certificates` without `softDeletes` — same pattern as `equipment_photos` (destroy cascade).
- `certificate_number` on both tables: the `calibrations.certificate_number` is a denormalized shortcut for list display; the `calibration_certificates.certificate_number` is the actual certificate-level number (could be multiple certs per calibration).

### CalibrationStatus Enum

Following the exact pattern of `LoanStatus.php`:

```php
<?php

namespace App\Enums;

enum CalibrationStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    public function canTransitionTo(CalibrationStatus $target): bool
    {
        return match ($this) {
            self::Scheduled => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed => false,   // terminal — completed is final
            self::Cancelled => false,   // terminal — cancelled is final
        };
    }
}
```

### Calibration Model

Following the exact pattern of `Loan.php`:

```php
<?php

namespace App\Models;

use App\Enums\CalibrationStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calibration extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'calibrations';

    protected $fillable = [
        'equipment_id', 'part_name', 'status', 'scheduled_date',
        'completed_at', 'next_due_at', 'interval_value', 'interval_unit',
        'responsible', 'laboratory', 'certificate_number', 'notes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
        'next_due_at' => 'datetime',
        'interval_value' => 'integer',
        'status' => CalibrationStatus::class,
    ];

    protected array $auditExclude = ['updated_by', 'deleted_by'];

    // ─── Relationships ────────────────────────────────────────────────

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withDefault();
    }

    public function certificates()
    {
        return $this->hasMany(CalibrationCertificate::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────

    public function getIsDueAttribute(): bool
    {
        return $this->status === CalibrationStatus::Completed
            && $this->next_due_at !== null
            && $this->next_due_at->isPast();
    }

    public function getIsDueSoonAttribute(): bool
    {
        return $this->status === CalibrationStatus::Completed
            && $this->next_due_at !== null
            && $this->next_due_at->isFuture()
            && $this->next_due_at->diffInDays(now()) <= 30;
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeDue(Builder $query): void
    {
        $query->where('status', CalibrationStatus::Completed)
              ->where('next_due_at', '<', now());
    }

    public function scopeDueSoon(Builder $query, int $days = 30): void
    {
        $query->where('status', CalibrationStatus::Completed)
              ->where('next_due_at', '>=', now())
              ->where('next_due_at', '<=', now()->addDays($days));
    }

    public function scopeByEquipment(Builder $query, string $equipmentId): void
    {
        $query->where('equipment_id', $equipmentId);
    }

    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('scheduled_date', [$from, $to]);
    }

    public function scopeByLaboratory(Builder $query, string $laboratory): void
    {
        $query->where('laboratory', 'ilike', "%{$laboratory}%");
    }
}
```

### CalibrationCertificate Model

Following the exact pattern of `EquipmentPhoto.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalibrationCertificate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'calibration_id', 'filename', 'filepath', 'mime_type', 'size_bytes',
        'certificate_number', 'issuer', 'issued_at', 'validity_start',
        'validity_end', 'notes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'issued_at' => 'date',
        'validity_start' => 'date',
        'validity_end' => 'date',
    ];

    public function calibration()
    {
        return $this->belongsTo(Calibration::class);
    }
}
```

### CalibrationService

Following the exact pattern of `LoanService.php` — business logic with transactions:

```php
<?php

namespace App\Services;

use App\Enums\CalibrationStatus;
use App\Exceptions\CalibrationException;
use App\Models\Calibration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalibrationService
{
    public function create(array $data): Calibration
    {
        return DB::transaction(function () use ($data) {
            $calibration = Calibration::create([
                'equipment_id' => $data['equipment_id'],
                'part_name' => $data['part_name'] ?? null,
                'status' => CalibrationStatus::Scheduled,
                'scheduled_date' => $data['scheduled_date'],
                'interval_value' => $data['interval_value'],
                'interval_unit' => $data['interval_unit'],
                'responsible' => $data['responsible'] ?? null,
                'laboratory' => $data['laboratory'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return $calibration->load(['equipment:id,name,patrimony_id']);
        });
    }

    public function complete(Calibration $calibration, array $data): Calibration
    {
        return DB::transaction(function () use ($calibration, $data) {
            if ($calibration->status !== CalibrationStatus::Scheduled) {
                throw new CalibrationException(
                    'Apenas calibrações com status "Agendada" podem ser concluídas.'
                );
            }

            $completedAt = isset($data['completed_at'])
                ? Carbon::parse($data['completed_at'])
                : now();

            $nextDueAt = $this->calculateNextDue($completedAt, $calibration->interval_value, $calibration->interval_unit);

            $calibration->update([
                'status' => CalibrationStatus::Completed,
                'completed_at' => $completedAt,
                'next_due_at' => $nextDueAt,
                'certificate_number' => $data['certificate_number'] ?? null,
                'responsible' => $data['responsible'] ?? $calibration->responsible,
                'laboratory' => $data['laboratory'] ?? $calibration->laboratory,
                'notes' => $data['notes'] ?? $calibration->notes,
            ]);

            return $calibration->fresh(['equipment:id,name,patrimony_id']);
        });
    }

    public function cancel(Calibration $calibration): Calibration
    {
        return DB::transaction(function () use ($calibration) {
            if ($calibration->status !== CalibrationStatus::Scheduled) {
                throw new CalibrationException(
                    'Apenas calibrações com status "Agendada" podem ser canceladas.'
                );
            }

            $calibration->update([
                'status' => CalibrationStatus::Cancelled,
            ]);

            return $calibration->fresh(['equipment:id,name,patrimony_id']);
        });
    }

    private function calculateNextDue(Carbon $completedAt, int $value, string $unit): Carbon
    {
        return match ($unit) {
            'months' => $completedAt->copy()->addMonths($value),
            'days' => $completedAt->copy()->addDays($value),
            'hours' => $completedAt->copy()->addHours($value),
            default => $completedAt->copy()->addMonths($value),
        };
    }

    public function checkDueSoon(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Calibration::dueSoon($days)
            ->with(['equipment:id,name,patrimony_id'])
            ->get();
    }
}
```

### CheckCalibrationDue Command

Adapted from `CheckOverdueLoans.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\CalibrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckCalibrationDue extends Command
{
    protected $signature = 'calibrations:check-due';
    protected $description = 'Check for calibrations due within 30 days and create in-app notifications';

    public function handle(): int
    {
        $this->info('Verificando calibrações próximas do vencimento...');

        $dueSoon = app(CalibrationService::class)->checkDueSoon(30);

        if ($dueSoon->isEmpty()) {
            $this->info('Nenhuma calibração próxima do vencimento encontrada.');
            return 0;
        }

        $this->info("Encontradas {$dueSoon->count()} calibração(ões) próxima(s) do vencimento.");

        $adminAndSupervisorUserIds = Role::whereIn('slug', ['admin', 'supervisor'])
            ->with('users:id')
            ->get()
            ->flatMap(fn (Role $role) => $role->users->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        if (empty($adminAndSupervisorUserIds)) {
            $this->warn('Nenhum usuário admin ou supervisor encontrado para notificar.');
            return 0;
        }

        $notificationsCreated = 0;

        foreach ($dueSoon as $calibration) {
            $daysUntilDue = (int) now()->diffInDays($calibration->next_due_at);
            $equipmentName = $calibration->equipment?->name ?? 'N/A';

            foreach ($adminAndSupervisorUserIds as $userId) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\Notifications\CalibrationDue',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $userId,
                    'data' => json_encode([
                        'calibration_id' => $calibration->id,
                        'equipment_name' => $equipmentName,
                        'next_due_at' => $calibration->next_due_at->format('d/m/Y'),
                        'days_until_due' => $daysUntilDue,
                        'message' => "Calibração de \"{$equipmentName}\" vence em {$daysUntilDue} dia(s).",
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $notificationsCreated++;
            }

            $this->info("Notificação criada para calibração {$calibration->id} — {$equipmentName} vence em {$daysUntilDue} dia(s)");
        }

        $this->info("{$notificationsCreated} notificação(ões) criada(s) para " . count($adminAndSupervisorUserIds) . " usuário(s).");

        return 0;
    }
}
```

**Schedule registration** in `AppServiceProvider.php` (alongside `loans:check-overdue`):

```php
$this->app->booted(function () {
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('loans:check-overdue')->daily();
    $schedule->command('calibrations:check-due')->daily();
});
```

### CalibrationController

Following the exact pattern of `LoanController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CalibrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalibrationRequest;
use App\Http\Requests\UpdateCalibrationRequest;
use App\Http\Requests\CompleteCalibrationRequest;
use App\Http\Resources\CalibrationCollection;
use App\Http\Resources\CalibrationResource;
use App\Models\Calibration;
use App\Services\CalibrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalibrationController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum', 'options' => ['only' => [
                'index', 'show', 'store', 'update', 'destroy', 'complete', 'cancel',
            ]]],
            ['middleware' => 'permission:calibracoes.view', 'options' => ['only' => ['index', 'show']]],
            ['middleware' => 'permission:calibracoes.create', 'options' => ['only' => ['store']]],
            ['middleware' => 'permission:calibracoes.edit', 'options' => ['only' => ['update', 'destroy']]],
            ['middleware' => 'permission:calibracoes.concluir', 'options' => ['only' => ['complete']]],
            ['middleware' => 'permission:calibracoes.cancel', 'options' => ['only' => ['cancel']]],
        ];
    }

    public function index(Request $request)
    {
        $equipment_id = $request->input('equipment_id');
        $status = $request->input('status');
        $from = $request->input('from');
        $to = $request->input('to');
        $laboratory = $request->input('laboratory');

        $calibrations = Calibration::query()
            ->with(['equipment:id,name,patrimony_id'])
            ->when($equipment_id, fn ($q) => $q->byEquipment($equipment_id))
            ->when($status, fn ($q) => $q->byStatus($status))
            ->when($from && $to, fn ($q) => $q->byDateRange($from, $to))
            ->when($laboratory, fn ($q) => $q->byLaboratory($laboratory))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return new CalibrationCollection($calibrations);
    }

    public function show(Calibration $calibration): CalibrationResource
    {
        $calibration->load(['equipment', 'createdBy', 'certificates']);

        return new CalibrationResource($calibration);
    }

    public function store(StoreCalibrationRequest $request)
    {
        $data = $request->validated();

        try {
            $calibration = app(CalibrationService::class)->create($data);
        } catch (CalibrationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'calibration_error',
            ], 422);
        }

        return (new CalibrationResource($calibration))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCalibrationRequest $request, Calibration $calibration)
    {
        if ($calibration->status !== CalibrationStatus::Scheduled) {
            return response()->json([
                'message' => 'Apenas calibrações com status "Agendada" podem ser editadas.',
                'error' => 'calibration_error',
            ], 422);
        }

        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $calibration->update($data);
        $calibration->load(['equipment:id,name,patrimony_id']);

        return new CalibrationResource($calibration);
    }

    public function destroy(Calibration $calibration): JsonResponse
    {
        $calibration->deleted_by = auth()->id();
        $calibration->save();
        $calibration->delete();

        return response()->json(null, 204);
    }

    public function complete(CompleteCalibrationRequest $request, Calibration $calibration)
    {
        try {
            $calibration = app(CalibrationService::class)->complete($calibration, $request->validated());
        } catch (CalibrationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'calibration_error',
            ], $e->getCode());
        }

        $calibration->load(['equipment:id,name,patrimony_id']);

        return new CalibrationResource($calibration);
    }

    public function cancel(Calibration $calibration)
    {
        try {
            $calibration = app(CalibrationService::class)->cancel($calibration);
        } catch (CalibrationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'calibration_error',
            ], $e->getCode());
        }

        $calibration->load(['equipment:id,name,patrimony_id']);

        return new CalibrationResource($calibration);
    }
}
```

### API Routes

Following the `api.php` pattern:

```php
// Calibrations Module
Route::apiResource('calibrations', CalibrationController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);
Route::prefix('calibrations/{calibration}')->group(function () {
    Route::post('complete', [CalibrationController::class, 'complete'])->name('calibrations.complete');
    Route::post('cancel', [CalibrationController::class, 'cancel'])->name('calibrations.cancel');
});

// Calibration Certificates (nested under calibration)
Route::prefix('calibrations/{calibration}/certificates')->group(function () {
    Route::get('/', [CalibrationCertificateController::class, 'index']);
    Route::post('/', [CalibrationCertificateController::class, 'store']);
    Route::get('/{certificate}/download', [CalibrationCertificateController::class, 'download']);
    Route::delete('/{certificate}', [CalibrationCertificateController::class, 'destroy']);
});
```

### CalibrationCertificateService

Following the exact pattern of `EquipmentPhotoService.php` but with PDF support:

```php
<?php

namespace App\Services;

use App\Models\CalibrationCertificate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CalibrationCertificateService
{
    private const MAX_SIZE = 10 * 1024 * 1024;   // 10MB (larger than photos)
    private const ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    private const DISK = 'public';

    public function upload(UploadedFile $file, string $calibrationId): CalibrationCertificate
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = "calibrations/{$calibrationId}/certificates/{$filename}";

        $stored = Storage::disk(self::DISK)->put($path, $file->get());

        if (!$stored) {
            throw new \RuntimeException('Falha ao armazenar certificado.');
        }

        return CalibrationCertificate::create([
            'calibration_id' => $calibrationId,
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }

    public function delete(string $certificateId): void
    {
        $certificate = CalibrationCertificate::findOrFail($certificateId);

        if (Storage::disk(self::DISK)->exists($certificate->filepath)) {
            Storage::disk(self::DISK)->delete($certificate->filepath);
        }

        $certificate->delete();
    }
}
```

### Frontend Architecture

#### Types (`frontend/src/modules/calibrations/types/calibration.ts`)

Following `loan.ts` pattern:

```typescript
export type CalibrationStatus = 'scheduled' | 'completed' | 'cancelled'

export interface CalibrationCertificate {
  id: string
  calibration_id: string
  filename: string
  filepath: string
  mime_type: string
  size_bytes: number
  certificate_number: string | null
  issuer: string | null
  issued_at: string | null
  validity_start: string | null
  validity_end: string | null
  notes: string | null
  created_at: string
}

export interface Calibration {
  id: string
  equipment: { id: string; name: string; patrimony_id?: string }
  part_name: string | null
  status: CalibrationStatus
  scheduled_date: string
  completed_at: string | null
  next_due_at: string | null
  interval_value: number
  interval_unit: 'months' | 'days' | 'hours'
  responsible: string | null
  laboratory: string | null
  certificate_number: string | null
  notes: string | null
  created_by: { id: string; name: string } | null
  certificates: CalibrationCertificate[]
  is_due: boolean
  is_due_soon: boolean
  created_at: string
  updated_at: string
}

export interface CalibrationFormData {
  equipment_id: string
  part_name?: string
  scheduled_date: string
  interval_value: number
  interval_unit: 'months' | 'days' | 'hours'
  responsible?: string
  laboratory?: string
  notes?: string
}

export interface CompleteCalibrationFormData {
  completed_at?: string
  certificate_number?: string
  responsible?: string
  laboratory?: string
  notes?: string
}

export const CALIBRATION_STATUS_OPTIONS = [
  { label: 'Agendada', value: 'scheduled' },
  { label: 'Concluída', value: 'completed' },
  { label: 'Cancelada', value: 'cancelled' },
]

export const INTERVAL_UNIT_OPTIONS = [
  { label: 'Meses', value: 'months' },
  { label: 'Dias', value: 'days' },
  { label: 'Horas', value: 'hours' },
]
```

#### Service (`frontend/src/modules/calibrations/services/CalibrationService.ts`)

Following `LoanService.ts` pattern:

```typescript
import { api } from '@/services/api'
import type { Calibration, CalibrationFormData, CompleteCalibrationFormData } from '../types/calibration'

export const calibrationService = {
  async list(params?: Record<string, any>) {
    const response = await api.get('/calibrations', { params })
    return response.data
  },

  async getById(id: string) {
    const response = await api.get(`/calibrations/${id}`)
    return response.data
  },

  async create(data: CalibrationFormData) {
    const response = await api.post('/calibrations', data)
    return response.data
  },

  async update(id: string, data: Partial<CalibrationFormData>) {
    const response = await api.put(`/calibrations/${id}`, data)
    return response.data
  },

  async delete(id: string) {
    await api.delete(`/calibrations/${id}`)
  },

  async complete(id: string, data: CompleteCalibrationFormData) {
    const response = await api.post(`/calibrations/${id}/complete`, data)
    return response.data
  },

  async cancel(id: string) {
    const response = await api.post(`/calibrations/${id}/cancel`)
    return response.data
  },

  async listEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    return response.data
  },

  // Certificates
  async listCertificates(calibrationId: string) {
    const response = await api.get(`/calibrations/${calibrationId}/certificates`)
    return response.data
  },

  async uploadCertificate(calibrationId: string, file: File) {
    const formData = new FormData()
    formData.append('certificate', file)
    const response = await api.post(`/calibrations/${calibrationId}/certificates`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async getCertificateUrl(certificateId: string): Promise<string> {
    return `${import.meta.env.VITE_API_URL}/storage/calibrations/certificates/${certificateId}`
  },

  async deleteCertificate(calibrationId: string, certificateId: string) {
    await api.delete(`/calibrations/${calibrationId}/certificates/${certificateId}`)
  },
}
```

**Important:** The actual storage path for certificate download should be resolved from the `CalibrationCertificate.filepath` field, not a constructed URL. The correct approach is to serve the file via a controller route (not direct storage URL), or construct the URL as:

```typescript
function getCertificateUrl(certificate: CalibrationCertificate): string {
  return `${import.meta.env.VITE_API_URL}/storage/${certificate.filepath}`
}
```

#### Store (`frontend/src/modules/calibrations/store/CalibrationStore.ts`)

Following `LoanStore.ts` pattern — Composition API Pinia store with `fetchAll`, `fetchById`, `create`, `update`, `destroy`, `complete`, `cancel`, `fetchEquipment`.

### Recommended Project Structure

```
frontend/src/modules/calibrations/
├── components/
│   ├── CalibrationInfoTab.vue          # Dados da calibração (DetailPage tab)
│   ├── CalibrationCertificateTab.vue   # Lista de certificados + upload
│   └── CalibrationTimelineTab.vue      # LogsActivity timeline
├── pages/
│   ├── CalibrationListPage.vue         # Lista com filtros (DataTable)
│   └── CalibrationDetailPage.vue       # DetailPage com 3 tabs
├── services/
│   └── CalibrationService.ts           # API calls
├── store/
│   └── CalibrationStore.ts             # Pinia store
├── types/
│   └── calibration.ts                  # TypeScript interfaces
└── routes/
    └── index.ts                        # (optional) route definitions

backend/
├── app/
│   ├── Console/Commands/
│   │   └── CheckCalibrationDue.php     # Scheduled alert command
│   ├── Enums/
│   │   └── CalibrationStatus.php       # Status enum
│   ├── Exceptions/
│   │   └── CalibrationException.php    # Business exception
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── CalibrationController.php
│   │   │   └── CalibrationCertificateController.php
│   │   ├── Requests/
│   │   │   ├── StoreCalibrationRequest.php
│   │   │   ├── UpdateCalibrationRequest.php
│   │   │   └── CompleteCalibrationRequest.php
│   │   └── Resources/
│   │       ├── CalibrationResource.php
│   │       └── CalibrationCollection.php
│   ├── Models/
│   │   ├── Calibration.php
│   │   └── CalibrationCertificate.php
│   ├── Notifications/
│   │   └── CalibrationDue.php          # Notification class (in-app only)
│   └── Services/
│       ├── CalibrationService.php
│       └── CalibrationCertificateService.php
└── database/migrations/
    └── 2026_07_25_000001_create_calibrations_tables.php
```

### Frontend List Page Pattern

The `CalibrationListPage.vue` follows the exact pattern of `LoanListPage.vue`:

- **Filters in Toolbar:** `Select` for equipment (fetched from `/equipments`), `Select` for status, `DatePicker` for date range (from/to), `InputText` for laboratory search.
- **DataTable with lazy pagination:** server-side pagination via `@page` event.
- **Status Tag:** `scheduled` = info, `completed` = success, `cancelled` = secondary.
- **Due indicators:** row class `p-row-due` for overdue calibrations (background highlight), tag `Vence em X dias` for due-soon items.
- **Action buttons:** View (eye), Edit (pencil, only when `scheduled`), Delete (trash, only when `scheduled` or `cancelled`).
- **Create dialog:** `CalibrationCreateDialog.vue` — modal with equipment select, optional part_name, date, interval (value + unit), responsible, laboratory, notes.

### Frontend Detail Page Pattern

The `CalibrationDetailPage.vue` follows the exact pattern of `LoanDetailPage.vue`:

- **Header:** Back button, title with status Tag, due Tag if applicable.
- **Action buttons:** "Concluir" (complete, when `scheduled` and user has `calibracoes.concluir`), "Cancelar" (when `scheduled` and user has `calibracoes.cancel`).
- **Tabs component:**
  - Tab 0: `CalibrationInfoTab` — displays all calibration fields (equipment, part, dates, interval, responsible, laboratory, notes)
  - Tab 1: `CalibrationCertificateTab` — list of certificates with upload button (FileUpload), download link, delete button
  - Tab 2: `CalibrationTimelineTab` — LogsActivity timeline (reuse `LoanTimelineTab.vue` pattern)
- **Complete dialog:** Modal with fields: completed_at (default now), certificate_number, responsible, laboratory, notes.

### Periodicidade Display

Format intervals for UI display using a utility function:

```typescript
function formatInterval(value: number, unit: string): string {
  const labels: Record<string, string> = {
    months: value === 1 ? 'mês' : 'meses',
    days: value === 1 ? 'dia' : 'dias',
    hours: value === 1 ? 'hora' : 'horas',
  }
  return `${value} ${labels[unit] || unit}`
}
// Examples: "6 meses", "30 dias", "1000 horas", "1 mês"
```

### Complete Action Dialog

The "Concluir" action opens a separate dialog (D-20) that:

1. Shows current calibration info (equipment name, scheduled_date, interval)
2. Pre-fills `completed_at` with `now`
3. Allows entering `certificate_number`, updating `responsible`/`laboratory`
4. On save, calls `POST /calibrations/{id}/complete`
5. Backend service sets status to `completed`, computes `next_due_at = completed_at + interval`

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Date arithmetic for next_due_at | Custom date math | Carbon (built-in Laravel): `->addMonths()`, `->addDays()`, `->addHours()` | Correct handling of month boundaries, leap years, DST |
| File upload handling | Raw `$_FILES` or `move_uploaded_file` | `Storage::disk('public')->put()` + `UploadedFile` | Consistent with existing EquipmentPhotoService pattern, symlink support |
| In-app notifications | Custom notification table management | `DB::table('notifications')` + JSON `data` column | Same pattern as CheckOverdueLoans, using existing notifications table |
| Permission middleware | Custom permission gates | `CheckPermission` middleware with `permission:calibracoes.*` | Already implemented and tested in phases 5-7 |
| Activity logging | Manual audit trail | `LogsActivity` trait | Already implemented in Phase 3, used across all models |

**Key insight:** Every technical challenge in Phase 8 already has a solved pattern in Phases 5, 6, or 7. The implementation risk is minimal if patterns are followed precisely.

## Common Pitfalls

### Pitfall 1: next_due_at computed on create instead of complete
**What goes wrong:** If `next_due_at` is set at creation time and the calibration remains `scheduled` for weeks, the due date will be wrong.
**Why it happens:** It's tempting to compute `next_due_at = scheduled_date + interval` at creation.
**How to avoid:** `next_due_at` MUST be computed from `completed_at` at the moment status transitions to `completed`. It remains `null` while `scheduled`.
**Warning signs:** A scheduled calibration showing a `next_due_at` value.

### Pitfall 2: Interval validation — negative or zero values
**What goes wrong:** Users might enter `interval_value: 0` or negative, causing infinite loops or past due dates.
**How to avoid:** In `StoreCalibrationRequest`, validate `interval_value` as `required|integer|min:1`. Validate `interval_unit` as `required|string|in:months,days,hours`.
**Warning signs:** `next_due_at` equal to or before `completed_at`.

### Pitfall 3: Overdue detection threshold
**What goes wrong:** The `isDue` accessor might be checked against the wrong date. "Due" means `next_due_at < now()`. But the alert command checks `next_due_at BETWEEN now() AND now() + 30 days`.
**Why it happens:** Confusion between "is overdue" (past due) and "is due soon" (within 30 days).
**How to avoid:** Use clear naming: `isDue` = past due, `isDueSoon` = within next 30 days. The command uses `dueSoon()` scope (30 days), not `due()` scope.
**Warning signs:** Notifications firing for calibrations that are 31+ days away, or missing ones that are 29 days away.

### Pitfall 4: Certificate file path construction
**What goes wrong:** The frontend constructs the URL as `storage/calibrations/{calibrationId}/certificates/{uuid}.ext` but the actual stored path uses the UUID format, not the certificate record ID.
**How to avoid:** The `CalibrationCertificate` model stores the full relative path (`filepath` field). The frontend reads this field and prepends the storage base URL: `` `${import.meta.env.VITE_API_URL}/storage/${cert.filepath}` ``. Never construct paths from IDs.
**Warning signs:** 404 errors when trying to download certificates.

### Pitfall 5: Missing `cascade` on calibration_certificates FK
**What goes wrong:** If a calibration is deleted (soft delete), its certificates remain orphaned in storage.
**How to avoid:** Use `->onDelete('cascade')` on the `calibration_id` FK (same as `equipment_photos`). For storage cleanup, the `CalibrationCertificateService::delete()` method handles individual deletions. For cascade deletes, add a `deleting` event listener or handle in the controller.
**Warning signs:** Orphaned files in `storage/app/public/calibrations/`.

## Code Examples — Verified Patterns from Codebase

### Existing Pattern: Timeline Tab (from loans)

`LoanTimelineTab.vue` pattern — reuse for calibration timeline:

```vue
<template>
  <div class="loan-timeline-tab">
    <Timeline :value="activities" align="left">
      <template #marker="{ item }">
        <span
          class="flex w-2rem h-2rem align-items-center justify-content-center border-circle"
          :class="getMarkerClass(item)"
        >
          <i :class="getIcon(item)" class="text-sm" />
        </span>
      </template>
      <template #content="{ item }">
        <div class="text-sm">{{ item.description }}</div>
        <div class="text-xs text-600">{{ formatDate(item.created_at) }}</div>
      </template>
    </Timeline>
  </div>
</template>
```

### Existing Pattern: Controller with static middleware (from loans)

```php
// LoanController.php — verified pattern
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum', 'options' => ['only' => [
            'index', 'show', 'store', 'update', 'destroy',
            'activate', 'returnItem', 'cancel',
        ]]],
        ['middleware' => 'permission:emprestimos.view', 'options' => ['only' => ['index', 'show']]],
        ['middleware' => 'permission:emprestimos.create', 'options' => ['only' => ['store']]],
        ['middleware' => 'permission:emprestimos.edit', 'options' => ['only' => ['update', 'destroy']]],
        ['middleware' => 'permission:emprestimos.finalizar', 'options' => ['only' => ['activate', 'returnItem', 'cancel']]],
    ];
}
```

### Existing Pattern: Service with DB::transaction (from LoanService)

```php
// LoanService.php — verified pattern
public function create(array $data): Loan
{
    return DB::transaction(function () use ($data) {
        // validation logic...
        $loan = Loan::create([...]);
        // attachment logic...
        return $loan->load(['borrower', 'equipment', 'items']);
    });
}
```

### Existing Pattern: Upload Service (from EquipmentPhotoService)

```php
// EquipmentPhotoService.php — verified pattern
private const MAX_SIZE = 5 * 1024 * 1024;
private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
private const DISK = 'public';

public function upload(UploadedFile $file, string $equipmentId): EquipmentPhoto
{
    $extension = $file->getClientOriginalExtension();
    $filename = Str::uuid() . '.' . $extension;
    $path = "equipment/{$equipmentId}/photos/{$filename}";

    $stored = Storage::disk(self::DISK)->put($path, $file->get());
    if (!$stored) {
        throw new RuntimeException('Falha ao armazenar foto.');
    }
    // ... create record
}
```

### Existing Pattern: Scheduled Command (from CheckOverdueLoans)

```php
// CheckOverdueLoans.php — verified pattern (full file at backend/app/Console/Commands/CheckOverdueLoans.php)
class CheckOverdueLoans extends Command
{
    protected $signature = 'loans:check-overdue';

    public function handle(): int
    {
        $overdueLoans = app(LoanService::class)->checkOverdue();
        // ... find admin/supervisor users
        // ... DB::table('notifications')->insert([...])
        return 0;
    }
}
```

### Existing Pattern: Schedule registration (from AppServiceProvider)

```php
// AppServiceProvider.php — verified pattern
$this->app->booted(function () {
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('loans:check-overdue')->daily();
});
```

### Existing Pattern: API Resource with whenLoaded (from LoanResource)

```php
// LoanResource.php — verified pattern
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'status' => $this->status?->value,
        'equipment' => $this->whenLoaded('equipment', fn () => ...),
        'created_at' => $this->created_at,
    ];
}
```

### Existing Pattern: Route registration (from api.php)

```php
// api.php — verified pattern
Route::apiResource('loans', LoanController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
Route::prefix('loans/{loan}')->group(function () {
    Route::post('activate', [LoanController::class, 'activate']);
    Route::post('return', [LoanController::class, 'returnItem']);
    Route::post('cancel', [LoanController::class, 'cancel']);
});
```

## Permission Model

Permissions to add in `RolePermissionSeeder.php` (D-21). The existing seeder already has `metrologia.view` and `metrologia.calibracoes.create`/`edit`. Based on D-21, the permissions should be replaced with a dedicated `calibracoes.*` group:

```php
// New permissions for Phase 8 (add to the $permissions array)
['name' => 'Visualizar Calibrações', 'slug' => 'calibracoes.view', 'group' => 'calibracoes'],
['name' => 'Criar Calibrações', 'slug' => 'calibracoes.create', 'group' => 'calibracoes'],
['name' => 'Editar Calibrações', 'slug' => 'calibracoes.edit', 'group' => 'calibracoes'],
['name' => 'Concluir Calibrações', 'slug' => 'calibracoes.concluir', 'group' => 'calibracoes'],
['name' => 'Cancelar Calibrações', 'slug' => 'calibracoes.cancel', 'group' => 'calibracoes'],
```

**Role mapping** (update existing roles):

| Role | Permissions |
|------|-------------|
| Admin | All `calibracoes.*` |
| Supervisor | `calibracoes.view`, `calibracoes.create`, `calibracoes.edit`, `calibracoes.concluir` |
| Laboratorista | `calibracoes.view`, `calibracoes.create`, `calibracoes.edit` (same as existing `metrologia.calibracoes.create`/`edit` but migrated) |
| Técnico | `calibracoes.view` |
| Consulta | `calibracoes.view` |
| Auditor | `calibracoes.view` |

**Important:** The existing `RolePermissionSeeder.php` has permissions with slug `metrologia.calibracoes.create` and `metrologia.calibracoes.edit`. These should be **migrated** (the old permissions removed or kept for backward compatibility). Since the seeder uses `updateOrInsert`, old permissions with the `metrologia.*` group will remain in the database but no longer be assigned to any role. The new permissions replace them. The planner should decide whether to explicitly delete the old permissions or leave them as orphans.

## Expressão de Periodicidade

### Display format
The interval is stored as `interval_value` (int) + `interval_unit` (string). Display in the UI as:

| Value | Unit | Display |
|-------|------|---------|
| 6 | months | "6 meses" |
| 1 | months | "1 mês" |
| 30 | days | "30 dias" |
| 1000 | hours | "1000 horas" |

### Computation of next_due_at
```php
use Carbon\Carbon;

private function calculateNextDue(Carbon $completedAt, int $value, string $unit): Carbon
{
    return match ($unit) {
        'months' => $completedAt->copy()->addMonths($value),
        'days'   => $completedAt->copy()->addDays($value),
        'hours'  => $completedAt->copy()->addHours($value),
    };
}
```

**Edge case — months:** `addMonths(6)` on Jan 31 returns Jul 31. `addMonths(1)` on Jan 31 returns Feb 28 (Carbon auto-adjusts). This is the correct behavior for calibration due dates. The agent's discretion includes whether to use calendar days or business days — recommendation: use calendar days for simplicity (matching Carbon defaults).

## Alerta de Vencimento

Based on D-11, D-12, D-13 (and the existing `CheckOverdueLoans.php`):

| Aspect | Implementation |
|--------|---------------|
| **Frequency** | Daily (`$schedule->command('calibrations:check-due')->daily()`) |
| **Query** | `Calibration::dueSoon(30)` — scope finds `completed` calibrations with `next_due_at BETWEEN now() AND now() + 30 days` |
| **Target audience** | Users with role `admin` or `supervisor` |
| **Notification channel** | In-app only (insert into `notifications` table) |
| **Notification data** | `calibration_id`, `equipment_name`, `next_due_at`, `days_until_due`, `message` |
| **Notification class** | `App\Notifications\CalibrationDue` (can be a simple marker class, or omitted entirely since we insert directly to DB) |

**Edge case — multiple notifications:** The command runs daily. It will create duplicate notifications if the same calibration is still due_soon on consecutive days. To prevent this, the command should check whether a notification for this calibration already exists for this user. Alternatively, accept duplicates (the user sees the same alert daily for 30 days, which is arguably desirable for urgency). Recommendation: accept duplicates — the existing `CheckOverdueLoans` command does not deduplicate either.

## Implementation Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| `interval_unit` with wrong values (hours = `h` instead of `hours`) | Runtime error in match() | Medium | Validate in FormRequest with `in:months,days,hours` |
| Storage symlink not created | Certificate uploads succeed but files return 404 | Medium | `php artisan storage:link` must be in setup script |
| `next_due_at` computed on wrong Carbon instance | Due dates off by hours/days | Low | Use `$completedAt->copy()` before mutation |
| Certificates FK cascade delete not set | Orphaned files on calibration delete | Medium | Add `->onDelete('cascade')` in migration |
| Permission slugs mismatch between seeder and middleware | 403 errors | Low | Keep a single source of truth in seeder, double-check slugs in controller middleware |

## Test Strategy

Following the existing pattern from `EquipmentApiTest.php`:

### Backend Tests

**Test file:** `backend/tests/Feature/CalibrationApiTest.php`

| Test | What It Covers |
|------|---------------|
| `test_unauthenticated_user_cannot_access_calibrations()` | Auth guard (401) |
| `test_can_list_calibrations()` | GET index, paginated response with meta |
| `test_can_create_calibration()` | POST store, 201 with validation |
| `test_can_show_calibration()` | GET show with relationships |
| `test_can_update_calibration()` | PUT update (status=scheduled only) |
| `test_cannot_update_completed_calibration()` | Status transition guard |
| `test_can_complete_calibration()` | POST complete, next_due_at computed |
| `test_next_due_at_is_correctly_computed()` | Days/months/hours calculation |
| `test_can_cancel_calibration()` | POST cancel |
| `test_can_delete_calibration()` | DELETE, soft delete |
| `test_can_filter_by_equipment()` | equipment_id query param |
| `test_can_filter_by_status()` | status query param |
| `test_can_filter_by_date_range()` | from/to query params |
| `test_can_upload_certificate()` | POST certificates, file validation |
| `test_can_delete_certificate()` | DELETE certificate, storage cleanup |

### Frontend Tests (manual — no frontend test framework configured)

Since no frontend test framework exists (noted in CONVENTIONS.md as a gap), testing is manual:
- Verify all pages render without console errors
- Verify CRUD operations work end-to-end
- Verify certificate upload/download
- Verify filter combinations
- Verify permission gating on UI actions

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `metrologia.calibracoes.create`/`edit` permissions | `calibracoes.*` dedicated group | Phase 8 | Old permissions remain in DB but unused; roles must be updated |
| None (new module) | Compound migration for calibrations + certificates | Phase 8 | Follows pattern from loans migration |
| None (new module) | CalibrationService with DB::transaction | Phase 8 | Follows LoanService pattern |

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | The `interval_unit` values `months`, `days`, `hours` as strings is sufficient. No `weeks` or `years` needed | Database Schema / Domain | Low — the scope is limited to lab equipment calibration intervals which typically are months or days |
| A2 | `CalibrationCertificateService` uses `Storage::disk('public')` with the same pattern as `EquipmentPhotoService` | File Upload Strategy | Low — same disk, same Storage facade; only MIME types differ |
| A3 | Notifications deduplication is not required (daily notifications for same calibration are acceptable) | Alerta de Vencimento | Medium — users may find repeated notifications annoying; planner should add a note to consider dedup if feedback indicates |
| A4 | The `notifications` table (created in Phase 7 migration) is available for Phase 8 | Alerta de Vencimento | Low — confirmed in `2026_07_21_000002_create_notifications_table.php` |

**If this table is empty:** All claims verified against existing codebase. The `[ASSUMED]` tag is used only for D-05 behavior and certificate upload MIME types which are new to this phase and not yet tested.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit ^12.5.12 |
| Config file | `backend/phpunit.xml` |
| Quick run command | `php artisan test --filter=Calibration` |
| Full suite command | `php artisan test` |
| DB driver | SQLite :memory: (configured in phpunit.xml) |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command |
|--------|----------|-----------|-------------------|
| CAL-01 | User can manage calibration schedule | unit + feature | `php artisan test --filter=CalibrationApiTest --filter=test_can_create_calibration` |
| CAL-02 | User can attach certificates | feature | `php artisan test --filter=CalibrationApiTest --filter=test_can_upload_certificate` |
| CAL-03 | System alerts when calibration is due | feature | `php artisan test --filter=CalibrationApiTest --filter=test_can_complete_calibration` (validates next_due_at computation) |
| CAL-04 | User can view history by equipment | feature | `php artisan test --filter=CalibrationApiTest --filter=test_can_filter_by_equipment` |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=CalibrationApiTest`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/CalibrationApiTest.php` — covers CAL-01 through CAL-04
- [ ] `tests/Feature/CalibrationCertificateTest.php` — certificate upload/download/delete (optional, can merge into CalibrationApiTest)

## Security Domain

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V4 Access Control | yes | Sanctum auth + CheckPermission middleware with `calibracoes.*` perms |
| V5 Input Validation | yes | FormRequest validation rules (StoreCalibrationRequest, etc.) |
| V8 File Uploads | yes | CalibrationCertificateService validates MIME type and size |
| V9 Data Protection | partial | Soft deletes, audit logging via LogsActivity trait |

### Known Threat Patterns
| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Upload malicious file (non-PDF/image) | Tampering | MIME validation (PDF + jpeg/png/webp only) + max size 10MB |
| Delete certificate without permission | Spoofing | CheckPermission middleware; authenticated user required |
| Access calibration of another scope | Information Disclosure | No multi-tenancy in v1; all users with `calibracoes.view` can see all |
| Notification flood | Denial of Service | Not applicable (daily cron, fixed set of recipients) |

## Sources

### Primary (HIGH confidence)
- Phase 7 loan module: `LoanController.php`, `LoanService.php`, `LoanStatus.php`, `LoanResource.php`, `Loan.php` — full CRUD + status transitions + service pattern
- Phase 5 photo module: `EquipmentPhotoService.php`, `EquipmentPhotoController.php` — upload/storage pattern
- Phase 5 migration: `2026_07_19_000002_create_equipments_tables.php` — compound migration pattern
- Phase 7 migration: `2026_07_21_000001_create_loans_tables.php` — compound migration + indexes
- Phase 7 command: `CheckOverdueLoans.php` — scheduled alert command pattern
- Phase 3 user model: `User.php` — HasUuids, LogsActivity traits
- Phase 7 frontend: `LoanListPage.vue`, `LoanDetailPage.vue`, `LoanService.ts`, `LoanStore.ts`, `loan.ts` — complete frontend module pattern
- Phase 4 navigation: `navigation.ts` — sidebar config + routeModuleMap
- Phase 7 route: `api.php` — route registration pattern

### Secondary (MEDIUM confidence)
- `AppServiceProvider.php` — schedule registration pattern
- `RolePermissionSeeder.php` — permission seeding pattern
- `DatabaseSeeder.php` — seeder integration pattern
- `EquipmentPhotoUploader.vue` — FileUpload component with FormData pattern
- `api.ts` — Axios instance with XSRF cookie handling

### Tertiary (LOW confidence)
- None — all patterns verified against existing codebase files

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new packages needed, verified against `composer.json` and `package.json`
- Architecture: HIGH — all patterns have direct analogs in Phases 5, 6, 7
- Pitfalls: HIGH — potential issues identified from live codebase reading
- Permission model: HIGH — seeded permissions follow existing pattern exactly

**Research date:** 2026-07-25
**Valid until:** Stack is stable; valid for duration of v0.2 milestone (~30 days)
