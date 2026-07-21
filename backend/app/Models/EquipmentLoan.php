<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EquipmentLoan extends Pivot
{
    use HasUuids;

    protected $table = 'equipment_loan';

    protected $fillable = [
        'loan_id', 'equipment_id', 'returned_at', 'notes',
    ];

    protected $casts = [
        'returned_at' => 'datetime',
    ];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    // ─── Acessors ───────────────────────────────────────────────────────

    /**
     * Verifica se o item foi devolvido.
     */
    public function getIsReturnedAttribute(): bool
    {
        return $this->returned_at !== null;
    }
}
