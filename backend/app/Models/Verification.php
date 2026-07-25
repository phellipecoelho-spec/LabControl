<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Verification extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'verifications';

    protected $fillable = [
        'equipment_id', 'verified_at', 'operator_id', 'notes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    protected array $auditExclude = ['updated_by', 'deleted_by'];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function params()
    {
        return $this->hasMany(VerificationParam::class)->orderBy('created_at');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope para filtrar aferições por equipamento.
     */
    public function scopeByEquipment(Builder $query, string $equipmentId): void
    {
        $query->where('equipment_id', $equipmentId);
    }

    /**
     * Scope para filtrar aferições por intervalo de datas.
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('verified_at', [$from, $to]);
    }
}
