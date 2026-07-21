<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'loans';

    protected $fillable = [
        'borrower_id', 'status', 'borrowed_at', 'expected_return_at',
        'returned_at', 'reason', 'destination', 'contact', 'notes',
        'approved_by', 'created_by', 'user_id', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'expected_return_at' => 'datetime',
        'returned_at' => 'datetime',
        'status' => LoanStatus::class,
    ];

    protected array $auditExclude = ['updated_by', 'deleted_by'];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by')->withDefault();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withDefault();
    }

    /**
     * Equipamentos associados via pivot equipment_loan.
     */
    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'equipment_loan')
            ->withPivot(['id', 'returned_at', 'notes'])
            ->withTimestamps()
            ->using(EquipmentLoan::class);
    }

    /**
     * Itens da pivot — atalho para consulta direta.
     */
    public function items()
    {
        return $this->hasMany(EquipmentLoan::class);
    }

    // ─── Acessors ───────────────────────────────────────────────────────

    /**
     * Verifica se o empréstimo está em atraso.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === LoanStatus::Active
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
    }

    /**
     * Quantidade de itens já devolvidos.
     */
    public function getReturnedItemsCountAttribute(): int
    {
        return $this->items()->whereNotNull('returned_at')->count();
    }

    /**
     * Quantidade total de itens no empréstimo.
     */
    public function getTotalItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope para empréstimos em atraso (status=active e expected_return_at no passado).
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', LoanStatus::Active)
              ->where('expected_return_at', '<', now());
    }

    /**
     * Scope para filtrar por status.
     */
    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope para filtrar por mutuário.
     */
    public function scopeByBorrower(Builder $query, string $borrowerId): void
    {
        $query->where('borrower_id', $borrowerId);
    }

    /**
     * Scope para filtrar por intervalo de datas (borrowed_at).
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('borrowed_at', [$from, $to]);
    }

    /**
     * Scope para filtrar por equipamento (via pivot).
     */
    public function scopeByEquipment(Builder $query, string $equipmentId): void
    {
        $query->whereHas('equipment', function (Builder $q) use ($equipmentId) {
            $q->where('equipment_loan.equipment_id', $equipmentId);
        });
    }
}
