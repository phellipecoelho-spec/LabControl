<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'item_id', 'type', 'quantity', 'balance_after',
        'reason', 'notes', 'user_id', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'balance_after' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter movements by type.
     */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope to filter movements by item.
     */
    public function scopeByItem(Builder $query, string $itemId): void
    {
        $query->where('item_id', $itemId);
    }

    /**
     * Scope to filter movements by responsible user.
     */
    public function scopeByUser(Builder $query, string $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope to filter movements within a date range.
     */
    public function scopeByDateRange(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('created_at', [$from, $to]);
    }
}
