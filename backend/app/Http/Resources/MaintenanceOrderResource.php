<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'description' => $this->description,
            'scheduled_date' => $this->scheduled_date,
            'completed_at' => $this->completed_at,
            'resolution' => $this->resolution,
            'time_spent' => $this->time_spent,
            'cost' => $this->cost,
            'interval_value' => $this->interval_value,
            'interval_unit' => $this->interval_unit,
            'next_due_at' => $this->next_due_at,
            'notes' => $this->notes,
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'name' => $this->equipment->name,
                'patrimony_id' => $this->equipment->patrimony_id,
            ]),
            'assigned_to' => $this->whenLoaded('assignedTo', fn () => [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ]),
            'opened_by' => $this->whenLoaded('openedBy', fn () => [
                'id' => $this->openedBy->id,
                'name' => $this->openedBy->name,
            ]),
            'parts' => $this->whenLoaded('parts', fn () => $this->parts->map(fn ($p) => [
                'id' => $p->id,
                'inventory_item_id' => $p->inventory_item_id,
                'item_name' => $p->item?->name,
                'quantity' => $p->quantity,
                'unit_cost' => $p->unit_cost,
            ])),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
