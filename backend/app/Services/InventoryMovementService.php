<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
    /**
     * Record an inventory movement with transactional safety.
     *
     * Implements THREE-LAYER negative stock defense:
     * 1. DB transaction — atomicity
     * 2. Row-level lock (FOR UPDATE) — prevents race conditions
     * 3. Application validation — user-friendly error before DB constraint
     *
     * @param  array  $data  Must contain: item_id, type, quantity, reason, notes
     * @return InventoryMovement
     *
     * @throws InsufficientStockException
     */
    public function recordMovement(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // Layer 2: Row-level lock to prevent concurrent read-check-write races
            /** @var InventoryItem $item */
            $item = InventoryItem::where('id', $data['item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Get current balance after lock is acquired
            $currentBalance = $item->current_balance;

            // Determine direction: purchase/return increment, others decrement
            $incrementingTypes = ['purchase', 'return'];
            $direction = in_array($data['type'], $incrementingTypes) ? 1 : -1;
            $quantity = (int) $data['quantity']; // always positive in payload
            $newBalance = $currentBalance + ($direction * $quantity);

            // Layer 3: Application validation
            if ($direction === -1 && $newBalance < 0) {
                throw new InsufficientStockException(
                    "Saldo insuficiente. Disponível: {$currentBalance}, necessário: {$quantity}"
                );
            }

            // Prepare movement data
            $data['balance_after'] = $newBalance;
            $data['user_id'] = auth()->id() ?? $data['user_id'];

            // Layer 1: DB transaction commits atomically
            return InventoryMovement::create($data);
        });
    }

    /**
     * Get the current balance for an item (O(1) read).
     *
     * @param  string  $itemId
     * @return int
     */
    public function getCurrentBalance(string $itemId): int
    {
        return InventoryMovement::where('item_id', $itemId)
            ->latest('created_at')
            ->value('balance_after') ?? 0;
    }
}
