<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MaintenanceOrderPart extends Pivot
{
    use HasUuids;

    protected $table = 'maintenance_order_parts';

    protected $fillable = [
        'maintenance_order_id', 'inventory_item_id', 'quantity', 'unit_cost', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
    ];

    // ─── Relacionamentos ────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(MaintenanceOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
