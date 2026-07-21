<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $incrementingTypes = ['purchase', 'return'];
        $direction = in_array($this->type, $incrementingTypes) ? 1 : -1;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'quantity_display' => $direction * $this->quantity,
            'balance_after' => $this->balance_after,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'item' => $this->whenLoaded('item', fn () => [
                'id' => $this->item->id,
                'name' => $this->item->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
