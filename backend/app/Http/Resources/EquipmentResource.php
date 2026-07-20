<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'patrimony_id' => $this->patrimony_id,
            'serial_number' => $this->serial_number,
            'location' => $this->location,
            'acquisition_date' => $this->acquisition_date,
            'warranty_end' => $this->warranty_end,
            'status' => $this->status,
            'description' => $this->description,
            'technical_specs' => $this->technical_specs,
            'notes' => $this->notes,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'manufacturer' => new ManufacturerResource($this->whenLoaded('manufacturer')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'path' => $photo->path,
                'url' => Storage::url($photo->path),
                'sort_order' => $photo->sort_order,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
