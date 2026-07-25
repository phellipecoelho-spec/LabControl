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

    // ─── Relacionamentos ────────────────────────────────────────────────

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

    /**
     * Certificados anexados a esta calibração.
     */
    public function certificates()
    {
        return $this->hasMany(CalibrationCertificate::class);
    }

    // ─── Accessors ───────────────────────────────────────────────────────

    /**
     * Verifica se a calibração está vencida (CAL-03).
     */
    public function getIsDueAttribute(): bool
    {
        return $this->status === CalibrationStatus::Completed
            && $this->next_due_at !== null
            && $this->next_due_at->isPast();
    }

    /**
     * Verifica se a calibração vence nos próximos 30 dias.
     */
    public function getIsDueSoonAttribute(): bool
    {
        return $this->status === CalibrationStatus::Completed
            && $this->next_due_at !== null
            && $this->next_due_at->isFuture()
            && $this->next_due_at->diffInDays(now()) <= 30;
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope para calibrações vencidas (completed com next_due_at no passado).
     */
    public function scopeDue(Builder $query): void
    {
        $query->where('status', CalibrationStatus::Completed)
              ->where('next_due_at', '<', now());
    }

    /**
     * Scope para calibrações próximas do vencimento (CAL-03).
     */
    public function scopeDueSoon(Builder $query, int $days = 30): void
    {
        $query->where('status', CalibrationStatus::Completed)
              ->where('next_due_at', '>=', now())
              ->where('next_due_at', '<=', now()->addDays($days));
    }

    /**
     * Scope para filtrar por equipamento (CAL-04).
     */
    public function scopeByEquipment(Builder $query, string $equipmentId): void
    {
        $query->where('equipment_id', $equipmentId);
    }

    /**
     * Scope para filtrar por status.
     */
    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope para filtrar por intervalo de datas (scheduled_date).
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('scheduled_date', [$from, $to]);
    }

    /**
     * Scope para filtrar por laboratório (busca case-insensitive).
     */
    public function scopeByLaboratory(Builder $query, string $laboratory): void
    {
        $query->where('laboratory', 'ilike', "%{$laboratory}%");
    }
}
