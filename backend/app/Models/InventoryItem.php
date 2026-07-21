<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'inventory_items';

    protected $fillable = [
        'name', 'code', 'description', 'category_id', 'supplier_id',
        'unit', 'min_stock', 'batch_lot', 'expiry_date', 'physical_location',
        'user_id', 'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'min_stock' => 'integer',
    ];

    protected $appends = [
        'current_balance',
        'is_critical',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'item_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withDefault();
    }

    /**
     * Get the current inventory balance from the latest movement.
     * O(1) read — uses balance_after from the most recent movement (D-10).
     */
    public function getCurrentBalanceAttribute(): int
    {
        return $this->movements()
            ->latest('created_at')
            ->value('balance_after') ?? 0;
    }

    /**
     * Determine if the item is at or below minimum stock (D-12).
     */
    public function getIsCriticalAttribute(): bool
    {
        return $this->current_balance <= $this->min_stock;
    }

    /**
     * Scope to search items by name or code.
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('code', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope to filter items by category.
     */
    public function scopeByCategory(Builder $query, string $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter items by supplier.
     */
    public function scopeBySupplier(Builder $query, string $supplierId): void
    {
        $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope to filter items by unit.
     */
    public function scopeByUnit(Builder $query, string $unit): void
    {
        $query->where('unit', $unit);
    }

    /**
     * Scope to filter items that are at critical stock level.
     * Uses a subquery to get the latest balance_after per item
     * from inventory_movements.
     */
    public function scopeCritical(Builder $query): void
    {
        $query->whereRaw(
            'coalesce((
                select im.balance_after
                from inventory_movements im
                where im.item_id = inventory_items.id
                order by im.created_at desc
                limit 1
            ), 0) <= inventory_items.min_stock'
        );
    }
}
