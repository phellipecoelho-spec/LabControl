# Phase 8: Calibrações — Pattern Map

**Mapped:** 2026-07-25
**Files analyzed:** 32 new/modified files
**Analogs found:** 30 / 32

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|---|---|---|---|---|
| `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php` | migration | — | `backend/database/migrations/2026_07_21_000001_create_loans_tables.php` | exact |
| `backend/app/Enums/CalibrationStatus.php` | enum | — | `backend/app/Enums/LoanStatus.php` | exact |
| `backend/app/Models/Calibration.php` | model | CRUD | `backend/app/Models/Loan.php` | exact |
| `backend/app/Models/CalibrationCertificate.php` | model | file-I/O | `backend/app/Models/EquipmentPhoto.php` | exact |
| `backend/app/Exceptions/CalibrationException.php` | utility | — | `backend/app/Exceptions/LoanException.php` | exact |
| `backend/app/Services/CalibrationService.php` | service | CRUD | `backend/app/Services/LoanService.php` | exact |
| `backend/app/Services/CalibrationCertificateService.php` | service | file-I/O | `backend/app/Services/EquipmentPhotoService.php` | exact |
| `backend/app/Http/Requests/StoreCalibrationRequest.php` | middleware | request-response | `backend/app/Http/Requests/StoreLoanRequest.php` | exact |
| `backend/app/Http/Requests/UpdateCalibrationRequest.php` | middleware | request-response | `backend/app/Http/Requests/UpdateLoanRequest.php` | exact |
| `backend/app/Http/Requests/CompleteCalibrationRequest.php` | middleware | request-response | `backend/app/Http/Requests/ReturnLoanItemRequest.php` | role-match |
| `backend/app/Http/Resources/CalibrationResource.php` | utility | transform | `backend/app/Http/Resources/LoanResource.php` | exact |
| `backend/app/Http/Resources/CalibrationCollection.php` | utility | transform | `backend/app/Http/Resources/LoanCollection.php` | exact |
| `backend/app/Http/Controllers/Api/V1/CalibrationController.php` | controller | CRUD | `backend/app/Http/Controllers/Api/V1/LoanController.php` | exact |
| `backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php` | controller | file-I/O | `backend/app/Http/Controllers/Api/V1/EquipmentPhotoController.php` | exact |
| `backend/app/Console/Commands/CheckCalibrationDue.php` | command | batch | `backend/app/Console/Commands/CheckOverdueLoans.php` | exact |
| `backend/app/Notifications/CalibrationDue.php` | notification | event-driven | No direct analog — notifications use `type` string in DB | N/A |
| `backend/routes/api.php` (modify) | route | request-response | Current `api.php` | exact |
| `backend/app/Providers/AppServiceProvider.php` (modify) | config | — | Current `AppServiceProvider.php` | exact |
| `backend/database/seeders/RolePermissionSeeder.php` (modify) | config | — | Current `RolePermissionSeeder.php` | exact |
| `frontend/src/modules/calibrations/types/calibration.ts` | types | — | `frontend/src/modules/loans/types/loan.ts` | exact |
| `frontend/src/modules/calibrations/services/CalibrationService.ts` | service | request-response | `frontend/src/modules/loans/services/LoanService.ts` | exact |
| `frontend/src/modules/calibrations/store/CalibrationStore.ts` | store | request-response | `frontend/src/modules/loans/store/LoanStore.ts` | exact |
| `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` | page | CRUD | `frontend/src/modules/loans/pages/LoanListPage.vue` | exact |
| `frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue` | page | CRUD | `frontend/src/modules/loans/pages/LoanDetailPage.vue` | exact |
| `frontend/src/modules/calibrations/components/CalibrationInfoTab.vue` | component | CRUD | `frontend/src/modules/loans/components/LoanInfoTab.vue` | exact |
| `frontend/src/modules/calibrations/components/CalibrationCertificateTab.vue` | component | file-I/O | `frontend/src/modules/loans/components/LoanItemsTab.vue` (list pattern) + `EquipmentPhotoUploader.vue` (upload) | partial |
| `frontend/src/modules/calibrations/components/CalibrationTimelineTab.vue` | component | event-driven | `frontend/src/modules/loans/components/LoanTimelineTab.vue` | exact |
| `frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue` | component | CRUD | `frontend/src/modules/loans/components/LoanCreateDialog.vue` | exact |
| `frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue` | component | CRUD | `frontend/src/modules/loans/components/LoanReturnDialog.vue` (modal pattern) | role-match |
| `frontend/src/modules/calibrations/components/CertificateUploadDialog.vue` | component | file-I/O | `EquipmentPhotoUploader.vue` (if exists) or `LoanReturnDialog.vue` | partial |
| `frontend/src/router/routes.ts` (modify) | route | — | Current `routes.ts` | exact |
| `frontend/src/types/navigation.ts` (verify) | config | — | Current `navigation.ts` | exact |

---

## Pattern Assignments

### `backend/database/migrations/2026_07_25_000001_create_calibrations_tables.php` (migration)

**Analog:** `backend/database/migrations/2026_07_21_000001_create_loans_tables.php`

**Import pattern** (lines 1-8):
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
```

**Core pattern — compound migration** (lines 13-68): Two tables in one migration: `calibrations` + `calibration_certificates`. UUID primary keys with `gen_random_uuid()`, foreign UUID keys, timestamps, softDeletes, composite indexes. Same structure as loans migration.

- `$table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'))` — line 17
- `$table->foreignUuid('equipment_id')->constrained('equipments')` — line 18
- `$table->foreignUuid('calibration_id')->constrained('calibrations')->onDelete('cascade')` — line 46 (for certificates)
- `$table->index(['status', 'next_due_at'])` — composite index for due query
- `$table->index(['equipment_id', 'status'])` — composite index for history filter

---

### `backend/app/Enums/CalibrationStatus.php` (enum)

**Analog:** `backend/app/Enums/LoanStatus.php`

**Full pattern** (lines 14-48):
```php
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
            self::Completed => false,
            self::Cancelled => false,
        };
    }
}
```

---

### `backend/app/Models/Calibration.php` (model)

**Analog:** `backend/app/Models/Loan.php` (lines 1-152)

**Import pattern** (lines 1-11):
```php
use App\Enums\CalibrationStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
```

**Trait + fillable + casts** (lines 15-31):
```php
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
```

**Relationship pattern** (lines 36-59, analog `Loan.php`):
```php
public function equipment()
{
    return $this->belongsTo(Equipment::class);
}

public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by')->withDefault();
}

public function certificates()
{
    return $this->hasMany(CalibrationCertificate::class);
}
```

**Accessor + Scope pattern** (lines 85-151, analog `Loan.php`):
```php
public function getIsOverdueAttribute(): bool
{
    return $this->status === LoanStatus::Active
        && $this->expected_return_at !== null
        && $this->expected_return_at->isPast();
}

public function scopeOverdue(Builder $query): void
{
    $query->where('status', LoanStatus::Active)
          ->where('expected_return_at', '<', now());
}
```

Adapt for Calibration:
```php
// isDueSoon accessor, scopeDueSoon, scopeDue, scopeByEquipment, scopeByStatus, scopeByDateRange, scopeByLaboratory
```

---

### `backend/app/Models/CalibrationCertificate.php` (model)

**Analog:** `backend/app/Models/EquipmentPhoto.php` (lines 1-24)

**Full pattern:**
```php
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

Key differences from analog: no `sort_order`, extra certificate metadata fields. No `softDeletes` (same as EquipmentPhoto).

---

### `backend/app/Exceptions/CalibrationException.php` (exception)

**Analog:** `backend/app/Exceptions/LoanException.php` (lines 1-32)

**Full pattern:**
```php
namespace App\Exceptions;

use Exception;

class CalibrationException extends Exception
{
    public function __construct(
        string $message = 'Operação de calibração inválida.',
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'calibration_error',
        ], $this->getCode());
    }
}
```

---

### `backend/app/Services/CalibrationService.php` (service)

**Analog:** `backend/app/Services/LoanService.php` (lines 1-281)

**Import pattern** (lines 1-11):
```php
use App\Enums\CalibrationStatus;
use App\Exceptions\CalibrationException;
use App\Models\Calibration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
```

**Transactional create pattern** (lines 33-83 analog):
```php
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
```

**Status transition pattern** (lines 180-195 analog):
```php
public function cancel(Calibration $calibration): Calibration
{
    return DB::transaction(function () use ($calibration) {
        if ($calibration->status !== CalibrationStatus::Scheduled) {
            throw new CalibrationException(
                'Apenas calibrações com status "Agendada" podem ser canceladas.'
            );
        }

        $calibration->update(['status' => CalibrationStatus::Cancelled]);

        return $calibration->fresh(['equipment:id,name,patrimony_id']);
    });
}
```

**Complete method** — unique to Calibration:
```php
public function complete(Calibration $calibration, array $data): Calibration
{
    return DB::transaction(function () use ($calibration, $data) {
        if ($calibration->status !== CalibrationStatus::Scheduled) {
            throw new CalibrationException('Apenas calibrações com status "Agendada" podem ser concluídas.');
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
```

**Check method** (lines 270-280 analog):
```php
public function checkDueSoon(int $days = 30): \Illuminate\Database\Eloquent\Collection
{
    return Calibration::dueSoon($days)
        ->with(['equipment:id,name,patrimony_id'])
        ->get();
}
```

---

### `backend/app/Services/CalibrationCertificateService.php` (service)

**Analog:** `backend/app/Services/EquipmentPhotoService.php` (lines 1-59)

**Full upload pattern** (lines 13-39):
```php
class CalibrationCertificateService
{
    private const MAX_SIZE = 10 * 1024 * 1024;   // 10MB (larger than photos)
    private const ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    private const DISK = 'public';

    public function upload(UploadedFile $file, string $calibrationId): CalibrationCertificate
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = "calibrations/certificates/{$filename}";

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
            // certificate_number, issuer, issued_at, validity_start, validity_end, notes
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

---

### `backend/app/Http/Requests/StoreCalibrationRequest.php` (form request)

**Analog:** `backend/app/Http/Requests/StoreLoanRequest.php` (lines 1-64)

Full pattern with `authorize()`, `rules()`, `messages()`.

**Validation rules** specific to calibration:
```php
public function rules(): array
{
    return [
        'equipment_id' => 'required|string|exists:equipments,id',
        'part_name' => 'nullable|string|max:255',
        'scheduled_date' => 'required|date|after_or_equal:today',
        'interval_value' => 'required|integer|min:1',
        'interval_unit' => 'required|string|in:months,days,hours',
        'responsible' => 'nullable|string|max:255',
        'laboratory' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:2000',
    ];
}
```

---

### `backend/app/Http/Requests/UpdateCalibrationRequest.php` (form request)

**Analog:** `backend/app/Http/Requests/UpdateLoanRequest.php` (lines 1-55)

Same pattern with `sometimes` rules instead of `required`.

---

### `backend/app/Http/Requests/CompleteCalibrationRequest.php` (form request)

**Analog:** `backend/app/Http/Requests/ReturnLoanItemRequest.php` (partial match)

```php
public function rules(): array
{
    return [
        'completed_at' => 'nullable|date',
        'certificate_number' => 'nullable|string|max:100',
        'responsible' => 'nullable|string|max:255',
        'laboratory' => 'nullable|string|max:255',
        'notes' => 'nullable|string|max:2000',
    ];
}
```

---

### `backend/app/Http/Resources/CalibrationResource.php` (resource)

**Analog:** `backend/app/Http/Resources/LoanResource.php` (lines 1-69)

**Pattern** — extends `JsonResource`, uses `whenLoaded` for relationships:
```php
class CalibrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'scheduled_date' => $this->scheduled_date,
            'completed_at' => $this->completed_at,
            'next_due_at' => $this->next_due_at,
            'interval_value' => $this->interval_value,
            'interval_unit' => $this->interval_unit,
            'responsible' => $this->responsible,
            'laboratory' => $this->laboratory,
            'certificate_number' => $this->certificate_number,
            'part_name' => $this->part_name,
            'notes' => $this->notes,
            'is_due' => $this->is_due,
            'is_due_soon' => $this->is_due_soon,
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'name' => $this->equipment->name,
                'patrimony_id' => $this->equipment->patrimony_id,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'certificates' => $this->whenLoaded('certificates'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

### `backend/app/Http/Resources/CalibrationCollection.php` (resource collection)

**Analog:** `backend/app/Http/Resources/LoanCollection.php` (lines 1-43)

**Pattern:**
```php
class CalibrationCollection extends ResourceCollection
{
    public $collects = CalibrationResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],
        ];
    }
}
```

---

### `backend/app/Http/Controllers/Api/V1/CalibrationController.php` (controller)

**Analog:** `backend/app/Http/Controllers/Api/V1/LoanController.php` (lines 1-196)

**Middleware pattern** (lines 24-36):
```php
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
```

**Index pattern** (lines 41-63 analog):
```php
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
```

**Store pattern** (lines 78-97 analog):
```php
public function store(StoreCalibrationRequest $request)
{
    try {
        $calibration = app(CalibrationService::class)->create($request->validated());
    } catch (CalibrationException $e) {
        return response()->json(['message' => $e->getMessage(), 'error' => 'calibration_error'], 422);
    }

    return (new CalibrationResource($calibration))
        ->response()
        ->setStatusCode(201);
}
```

**Status-action pattern** (lines 136-195 analog):
```php
public function complete(CompleteCalibrationRequest $request, Calibration $calibration)
{
    try {
        $calibration = app(CalibrationService::class)->complete($calibration, $request->validated());
    } catch (CalibrationException $e) {
        return response()->json(['message' => $e->getMessage(), 'error' => 'calibration_error'], $e->getCode());
    }

    $calibration->load(['equipment:id,name,patrimony_id']);

    return new CalibrationResource($calibration);
}
```

---

### `backend/app/Http/Controllers/Api/V1/CalibrationCertificateController.php` (controller)

**Analog:** `backend/app/Http/Controllers/Api/V1/EquipmentPhotoController.php` (lines 1-59)

**Pattern:**
```php
class CalibrationCertificateController extends Controller
{
    public static function middleware(): array
    {
        return [
            ['middleware' => 'auth:sanctum'],
            ['middleware' => 'permission:calibracoes.edit'],
        ];
    }

    public function index(Calibration $calibration): JsonResponse
    {
        $certificates = $calibration->certificates()->orderBy('created_at', 'desc')->get();
        return response()->json($certificates);
    }

    public function store(Request $request, Calibration $calibration): JsonResponse
    {
        $validated = $request->validate([
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $certificate = app(CalibrationCertificateService::class)->upload($validated['certificate'], $calibration->id);

        return response()->json($certificate, 201);
    }

    public function download(Calibration $calibration, string $certificate): \Illuminate\Http\StreamedResponse
    {
        $cert = CalibrationCertificate::findOrFail($certificate);
        return Storage::disk('public')->download($cert->filepath, $cert->filename);
    }

    public function destroy(Calibration $calibration, string $certificate): JsonResponse
    {
        app(CalibrationCertificateService::class)->delete($certificate);
        return response()->json(null, 204);
    }
}
```

---

### `backend/app/Console/Commands/CheckCalibrationDue.php` (command)

**Analog:** `backend/app/Console/Commands/CheckOverdueLoans.php` (lines 1-93)

**Full pattern** — adapted from line-by-line analog:
```php
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

        // Find admin/supervisor users — same pattern as CheckOverdueLoans
        $adminAndSupervisorUserIds = Role::whereIn('slug', ['admin', 'supervisor'])
            ->with('users:id')
            ->get()
            ->flatMap(fn (Role $role) => $role->users->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        // Insert in-app notifications — same pattern as CheckOverdueLoans
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
            }
        }

        return 0;
    }
}
```

---

### `backend/app/Notifications/CalibrationDue.php` (notification)

**No direct analog needed.** The CheckOverdueLoans pattern writes directly to the `notifications` table via `DB::table('notifications')->insert([...])`, using a `type` string `'App\Notifications\CalibrationDue'` that can be a marker class if notification class resolution is needed. Create a minimal notification class if Laravel's notification system requires it for polymorphic type resolution:

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CalibrationDue extends Notification
{
    // Marker class — data is stored directly in JSON via the command
}
```

---

### `backend/routes/api.php` (modify)

**Analog:** current `api.php` lines 88-94 (loans module)

**Route pattern to add inside `Route::middleware('auth:sanctum')->group(function ()`**:
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

---

### `backend/app/Providers/AppServiceProvider.php` (modify)

**Analog:** current `AppServiceProvider.php` lines 28-32

**Schedule registration** — add alongside existing loan schedule:
```php
$this->app->booted(function () {
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('loans:check-overdue')->daily();
    $schedule->command('calibrations:check-due')->daily();
});
```

---

### `backend/database/seeders/RolePermissionSeeder.php` (modify)

**Analog:** current `RolePermissionSeeder.php` lines 1-182

**Permissions to add** in the `$permissions` array (keep existing, add these):
```php
['name' => 'Visualizar Calibrações', 'slug' => 'calibracoes.view', 'group' => 'calibracoes'],
['name' => 'Criar Calibrações', 'slug' => 'calibracoes.create', 'group' => 'calibracoes'],
['name' => 'Editar Calibrações', 'slug' => 'calibracoes.edit', 'group' => 'calibracoes'],
['name' => 'Concluir Calibrações', 'slug' => 'calibracoes.concluir', 'group' => 'calibracoes'],
['name' => 'Cancelar Calibrações', 'slug' => 'calibracoes.cancel', 'group' => 'calibracoes'],
```

**Note:** Old `metrologia.calibracoes.create` and `metrologia.calibracoes.edit` permissions can remain in DB — the seeder uses `updateOrInsert` and they won't be assigned to any role after roles are re-seeded.

---

### `frontend/src/modules/calibrations/types/calibration.ts` (types)

**Analog:** `frontend/src/modules/loans/types/loan.ts` (lines 1-78)

**Full type definitions:**
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

---

### `frontend/src/modules/calibrations/services/CalibrationService.ts` (service)

**Analog:** `frontend/src/modules/loans/services/LoanService.ts` (lines 1-53)

**Full pattern:**
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

  async deleteCertificate(calibrationId: string, certificateId: string) {
    await api.delete(`/calibrations/${calibrationId}/certificates/${certificateId}`)
  },
}
```

---

### `frontend/src/modules/calibrations/store/CalibrationStore.ts` (store)

**Analog:** `frontend/src/modules/loans/store/LoanStore.ts` (lines 1-128)

**Full pattern** — Composition API Pinia store:
```typescript
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Calibration, CalibrationFormData, CompleteCalibrationFormData } from '../types/calibration'
import type { Equipment } from '@/modules/equipment/types/equipment'

interface Pagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export const useCalibrationStore = defineStore('calibration', () => {
  const calibrations = ref<Calibration[]>([])
  const currentCalibration = ref<Calibration | null>(null)
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  })
  const equipment = ref<Equipment[]>([])

  async function fetchAll(params?: Record<string, any>) {
    loading.value = true
    try {
      const response = await api.get('/calibrations', { params })
      const data = response.data
      if (Array.isArray(data)) {
        calibrations.value = data
      } else if (data.data) {
        calibrations.value = data.data
        pagination.value = {
          current_page: data.current_page ?? 1,
          last_page: data.last_page ?? 1,
          total: data.total ?? 0,
          per_page: data.per_page ?? 15,
        }
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: string) {
    loading.value = true
    try {
      const response = await api.get(`/calibrations/${id}`)
      currentCalibration.value = response.data?.data ?? response.data
      return currentCalibration.value
    } finally {
      loading.value = false
    }
  }

  async function create(data: CalibrationFormData) {
    const response = await api.post('/calibrations', data)
    return response.data
  }

  async function update(id: string, data: Partial<CalibrationFormData>) {
    const response = await api.put(`/calibrations/${id}`, data)
    return response.data
  }

  async function destroy(id: string) {
    await api.delete(`/calibrations/${id}`)
  }

  async function complete(id: string, data: CompleteCalibrationFormData) {
    const response = await api.post(`/calibrations/${id}/complete`, data)
    return response.data
  }

  async function cancel(id: string) {
    const response = await api.post(`/calibrations/${id}/cancel`)
    return response.data
  }

  async function fetchEquipment(params?: Record<string, any>) {
    const response = await api.get('/equipments', { params })
    equipment.value = response.data?.data ?? response.data ?? []
    return equipment.value
  }

  return {
    calibrations, currentCalibration, loading, pagination, equipment,
    fetchAll, fetchById, create, update, destroy, complete, cancel, fetchEquipment,
  }
})
```

---

### `frontend/src/modules/calibrations/pages/CalibrationListPage.vue` (page)

**Analog:** `frontend/src/modules/loans/pages/LoanListPage.vue` (lines 1-358)

**Key template patterns to copy** (line numbers from analog):
- Toolbar with filters (lines 19-71): `InputText`, `Select` for status, `Select` for equipment, `DatePicker` for date range (from/to)
- DataTable with lazy pagination (lines 73-168): `@page`, `lazy`, `firstRow`, `stripedRows`, `rowClass`
- Status Tag with severity mapping (lines 117-135)
- Action buttons column with per-permission visibility (lines 136-167)
- `onSearch` debounce (lines 232-243)
- `handleFilterChange` → `fetchLoans(1)` (lines 245-247)
- `rowClass` for visual indicators (lines 234-236)
- `getStatusLabel`, `getStatusSeverity`, `formatDate` utility functions

**Calibration-specific adjustments:**
- Filters: equipment (Select), status (Select), date range (DatePicker from/to), laboratory (InputText)
- Columns: Equipamento, Parte, Data Agendada, Data Conclusão, Próxima Data, Laboratório, Status, Ações
- Status severity: `scheduled` = info, `completed` = success, `cancelled` = secondary
- Due indicators: row class `p-row-due` for overdue, tag "Vence em X dias" for due-soon
- Actions: View (eye all), Edit (pencil, only `scheduled`), Delete (trash, only `scheduled`/`cancelled`)

---

### `frontend/src/modules/calibrations/pages/CalibrationDetailPage.vue` (page)

**Analog:** `frontend/src/modules/loans/pages/LoanDetailPage.vue` (lines 1-282)

**Key template patterns to copy** (line numbers from analog):
- Back button + title with status Tag (lines 7-31)
- Permission-gated action buttons (lines 33-58): "Concluir" (calibracoes.concluir, when `scheduled`), "Cancelar" (calibracoes.cancel, when `scheduled`)
- Tabs component with 3 panels (lines 91-111): Info, Certificates, Timeline
- Skeleton loading state (lines 61-65)
- Toast + ConfirmDialog integration
- `confirmActivate`, `confirmCancel` patterns adapted to `confirmComplete`, `confirmCancel`

---

### `frontend/src/modules/calibrations/components/CalibrationInfoTab.vue` (component)

**Analog:** `frontend/src/modules/loans/components/LoanInfoTab.vue` (lines 1-179)

Full pattern with `Card` groups, `Divider`, `field` labels. Display calibration-specific fields: equipment name, part name, scheduled date, completed date, next due date, interval display, responsible, laboratory, notes.

---

### `frontend/src/modules/calibrations/components/CalibrationCertificateTab.vue` (component)

**Partial analog:** combines list pattern from `LoanItemsTab.vue` with upload pattern from `EquipmentPhotoUploader.vue`.

List certificates with file details (filename, mime_type, size, issuer, validity), download button, delete button. Upload button opens a file dialog using PrimeVue `FileUpload` component.

---

### `frontend/src/modules/calibrations/components/CalibrationTimelineTab.vue` (component)

**Analog:** `frontend/src/modules/loans/components/LoanTimelineTab.vue` (lines 1-153)

**Full pattern** — PrimeVue `Timeline` component with computed events based on model state:
```vue
<Timeline :value="timelineEvents" align="left">
  <template #marker="{ item }">
    <span class="flex align-items-center justify-content-center w-2rem h-2rem border-circle z-1 shadow-2"
      :style="{ backgroundColor: item.color, color: '#fff' }">
      <i :class="item.icon" class="text-sm" />
    </span>
  </template>
  <template #content="{ item }">
    <Card>
      <template #title><span class="text-sm font-medium">{{ item.title }}</span></template>
      <template #subtitle><span class="text-xs text-600">{{ item.date }}</span></template>
      <template #content>
        <p v-if="item.description" class="text-sm m-0 text-700">{{ item.description }}</p>
      </template>
    </Card>
  </template>
</Timeline>
```

Calibration-specific events: created, completed (with next_due_at), cancelled.

---

### `frontend/src/modules/calibrations/components/CalibrationCreateDialog.vue` (dialog)

**Analog:** `frontend/src/modules/loans/components/LoanCreateDialog.vue` (lines 1-291)

Full pattern: `Dialog` with `v-model:visible`, form fields with validation highlights, `handleSave` with `submitted` guard, `resetForm`, `saving` state, `emit('saved')`.

Calibration-specific fields: Equipment (Select), Part Name (InputText), Scheduled Date (DatePicker), Interval Value (InputNumber), Interval Unit (Select: months/days/hours), Responsible (InputText), Laboratory (InputText), Notes (Textarea).

---

### `frontend/src/modules/calibrations/components/CalibrationConcludeDialog.vue` (dialog)

**Role-match analog:** `LoanReturnDialog.vue` (modal with form + submit)

Dialog modal with fields: Completed At (DatePicker, default today), Certificate Number (InputText), Responsible (InputText, pre-filled), Laboratory (InputText), Notes (Textarea). On save calls `POST /calibrations/{id}/complete`.

---

### `frontend/src/router/routes.ts` (modify)

**Analog:** current `routes.ts` lines 141-145 (placeholder)

Replace placeholder:
```typescript
{
  path: '/calibrations',
  name: 'calibrations.index',
  component: () => import('@/modules/calibrations/pages/CalibrationListPage.vue'),
  meta: { requiresAuth: true, module: 'calibrations.index', title: 'Calibrações' },
},
{
  path: '/calibrations/:id',
  name: 'calibrations.show',
  component: () => import('@/modules/calibrations/pages/CalibrationDetailPage.vue'),
  meta: { requiresAuth: true, module: 'calibrations.index', title: 'Detalhes da Calibração' },
},
```

---

### `frontend/src/types/navigation.ts` (verify)

**Analog:** current `navigation.ts` lines 62-66

Already has calibration entry with `permission: 'calibracoes.view'`. Route map already has `'calibrations.index': 'operacoes'` (line 143). Module key `'calibrations.create'` should be added to `routeModuleMap` for any future create route. No changes needed for existing navigation item.

---

## Shared Patterns

### Authentication
**Source:** `backend/app/Http/Controllers/Api/V1/LoanController.php` lines 24-36
**Apply to:** `CalibrationController`, `CalibrationCertificateController`
```php
public static function middleware(): array
{
    return [
        ['middleware' => 'auth:sanctum', 'options' => ['only' => [...]]],
        ['middleware' => 'permission:calibracoes.*', 'options' => ['only' => [...]]],
    ];
}
```

### Transactional Service Pattern
**Source:** `backend/app/Services/LoanService.php` lines 33-83
**Apply to:** `CalibrationService`
```php
return DB::transaction(function () use ($data) {
    // validate business rules
    // create/update model
    // return with relationships loaded
});
```

### Error Handling with Business Exception
**Source:** `backend/app/Exceptions/LoanException.php` (lines 1-32) + `LoanController.php` lines 82-89
**Apply to:** All controller custom actions
```php
try {
    $result = app(Service::class)->method($data);
} catch (CalibrationException $e) {
    return response()->json([
        'message' => $e->getMessage(),
        'error' => 'calibration_error',
    ], 422);
}
```

### Status Transition Guard Pattern
**Source:** `backend/app/Services/LoanService.php` lines 97-101
**Apply to:** `CalibrationService::complete()`, `CalibrationService::cancel()`
```php
if ($calibration->status !== CalibrationStatus::Scheduled) {
    throw new CalibrationException('Apenas calibrações com status "Agendada" podem ser concluídas/canceladas.');
}
```

### Soft Delete with deleted_by
**Source:** `backend/app/Http/Controllers/Api/V1/LoanController.php` lines 124-131
**Apply to:** `CalibrationController::destroy()`
```php
public function destroy(Calibration $calibration): JsonResponse
{
    $calibration->deleted_by = auth()->id();
    $calibration->save();
    $calibration->delete();

    return response()->json(null, 204);
}
```

### Upload Service Pattern
**Source:** `backend/app/Services/EquipmentPhotoService.php` (lines 1-59)
**Apply to:** `CalibrationCertificateService`
- UUID-based filename generation
- Storage disk `public`
- `Storage::disk()->put()` with `$file->get()`
- Error throw on `!$stored`
- Delete: find record → delete file from storage → delete DB record

### Frontend List Page Pattern
**Source:** `frontend/src/modules/loans/pages/LoanListPage.vue`
**Apply to:** `CalibrationListPage.vue`
- `Toast` + `ConfirmDialog` at top
- `Toolbar` with filter components
- `DataTable` with lazy pagination, `stripedRows`, `rowClass`
- `@page` event → `fetchLoans(event.page + 1)`
- Debounced search with `setTimeout`
- Per-permission action buttons
- `getStatusLabel`/`getStatusSeverity` mapping functions

### Frontend Detail Page Pattern
**Source:** `frontend/src/modules/loans/pages/LoanDetailPage.vue`
**Apply to:** `CalibrationDetailPage.vue`
- Back button + title with status tag
- Permission-gated action buttons
- `Tabs` with `TabList`/`TabPanels` (3 tabs)
- `Skeleton` loading state
- `onMounted` → `store.fetchById(id)`
- `confirm.*` for destructive actions
- Toast feedback on success/error

### Frontend Pinia Store Pattern
**Source:** `frontend/src/modules/loans/store/LoanStore.ts`
**Apply to:** `CalibrationStore.ts`
- `defineStore` with composition API
- `Pagination` interface
- `fetchAll` with pagination response handling
- `fetchById` with `loading` wrapper
- `create`, `update`, `destroy`, action-methods
- `fetchEquipment` helper for select options

### Permission Pattern (Frontend)
**Source:** `frontend/src/stores/auth.ts` lines 39-43
**Apply to:** All Vue components
```typescript
function hasPermission(perm: string): boolean {
    return user.value?.roles?.some(r =>
      r.permissions?.some(p => p.slug === perm)
    ) ?? false
}
```

Usage in components: `v-if="authStore.hasPermission('calibracoes.create')"`

### Axios API Pattern
**Source:** `frontend/src/services/api.ts`

The `api` instance is imported directly — no need to create a new Axios instance per module. Module services just call `api.get`, `api.post`, etc.

---

## No Analog Found

| File | Role | Data Flow | Reason |
|------|------|-----------|--------|
| `backend/app/Notifications/CalibrationDue.php` | notification | event-driven | Notifications use DB insert pattern, no full OOP analog needed |
| `frontend/src/modules/calibrations/components/CertificateUploadDialog.vue` | component | file-I/O | Equipment photo uploader is inline in EquipmentPhotoController, no separate dialog component exists yet |

---

## Metadata

**Analog search scope:** `backend/app/` (Models, Controllers, Services, Enums, Exceptions, Commands, Requests, Resources, Traits, Providers), `frontend/src/modules/loans/` (pages, components, services, store, types), `frontend/src/services/`, `frontend/src/stores/`, `frontend/src/router/`, `frontend/src/types/`, `backend/routes/`, `backend/database/migrations/`, `backend/database/seeders/`
**Files scanned:** ~50 analog files
**Pattern extraction date:** 2026-07-25
