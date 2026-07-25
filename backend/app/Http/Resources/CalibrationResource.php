<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalibrationResource extends JsonResource
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
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'scheduled_date' => $this->scheduled_date,
            'completed_at' => $this->completed_at,
            'next_due_at' => $this->next_due_at,
            'interval_value' => $this->interval_value,
            'interval_unit' => $this->interval_unit,
            'part_name' => $this->part_name,
            'responsible' => $this->responsible,
            'laboratory' => $this->laboratory,
            'certificate_number' => $this->certificate_number,
            'notes' => $this->notes,
            'is_due' => $this->is_due,
            'is_due_soon' => $this->is_due_soon,
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'name' => $this->equipment->name,
                'patrimony_id' => $this->equipment->patrimony_id,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'certificates' => $this->whenLoaded('certificates'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
