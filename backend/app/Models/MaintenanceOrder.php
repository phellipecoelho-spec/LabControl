<?php

namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'maintenance_orders';

    protected $fillable = [
        'equipment_id', 'type', 'status', 'priority', 'description',
        'scheduled_date', 'assigned_to', 'opened_by',
        'completed_at', 'resolution', 'time_spent', 'cost',
        'interval_value', 'interval_unit', 'next_due_at', 'notes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'status' => MaintenanceStatus::class,
        'type' => MaintenanceType::class,
        'priority' => MaintenancePriority::class,
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
        'next_due_at' => 'datetime',
        'interval_value' => 'integer',
        'time_spent' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    protected array $auditExclude = ['updated_by', 'deleted_by'];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault();
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withDefault();
    }

    public function parts()
    {
        return $this->hasMany(MaintenanceOrderPart::class);
    }

    // ─── Accessors ───────────────────────────────────────────────────────

    /**
     * Verifica se a ordem está atrasada (não concluída/cancelada e scheduled_date no passado).
     */
    public function getIsOverdueAttribute(): bool
    {
        return !in_array($this->status, [MaintenanceStatus::Completed, MaintenanceStatus::Cancelled], true)
            && $this->scheduled_date !== null
            && $this->scheduled_date->isPast();
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope para filtrar por equipamento.
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
     * Scope para filtrar por tipo.
     */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope para filtrar por prioridade.
     */
    public function scopeByPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * Scope para filtrar por intervalo de datas (scheduled_date).
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('scheduled_date', [$from, $to]);
    }
}
