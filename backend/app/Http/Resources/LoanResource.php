<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalItems = $this->total_items_count;
        $returnedItems = $this->returned_items_count;
        $progress = $totalItems > 0 ? round(($returnedItems / $totalItems) * 100) : 0;

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'borrowed_at' => $this->borrowed_at,
            'expected_return_at' => $this->expected_return_at,
            'returned_at' => $this->returned_at,
            'reason' => $this->reason,
            'destination' => $this->destination,
            'contact' => $this->contact,
            'notes' => $this->notes,
            'is_overdue' => $this->is_overdue,
            'items_count' => $totalItems,
            'returned_items_count' => $returnedItems,
            'progress' => $progress,
            'borrower' => $this->whenLoaded('borrower', fn () => [
                'id' => $this->borrower->id,
                'name' => $this->borrower->name,
                'email' => $this->borrower->email,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
                'email' => $this->approvedBy->email,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ]),
            'equipment' => $this->whenLoaded('equipment', fn () =>
                $this->equipment->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'patrimony_id' => $item->patrimony_id,
                    'serial_number' => $item->serial_number,
                    'pivot' => [
                        'id' => $item->pivot->id,
                        'returned_at' => $item->pivot->returned_at,
                        'notes' => $item->pivot->notes,
                        'is_returned' => $item->pivot->is_returned ?? $item->pivot->returned_at !== null,
                    ],
                ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
