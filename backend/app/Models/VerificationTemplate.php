<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'verification_templates';

    protected $fillable = [
        'equipment_category_id', 'parameter_name', 'parameter_unit',
        'tolerance_min', 'tolerance_max', 'sort_order',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'tolerance_min' => 'decimal:6',
        'tolerance_max' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class, 'equipment_category_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope para filtrar templates por categoria de equipamento.
     */
    public function scopeByCategory(Builder $query, string $categoryId): void
    {
        $query->where('equipment_category_id', $categoryId);
    }
}
