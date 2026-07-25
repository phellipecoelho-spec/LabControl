<?php

namespace App\Models;

use App\Enums\VerificationResult;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationParam extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'verification_params';

    protected $fillable = [
        'verification_id', 'template_id', 'value', 'result', 'notes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'result' => VerificationResult::class,
    ];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function verification()
    {
        return $this->belongsTo(Verification::class);
    }

    public function template()
    {
        return $this->belongsTo(VerificationTemplate::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    // ─── Accessors ───────────────────────────────────────────────────────

    /**
     * Rótulo do resultado para exibição na UI.
     */
    public function getResultLabelAttribute(): ?string
    {
        return $this->result?->label();
    }
}
