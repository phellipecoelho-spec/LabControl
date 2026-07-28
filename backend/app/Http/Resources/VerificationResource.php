<?php

namespace App\Http\Resources;

use App\Enums\VerificationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
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
            'verified_at' => $this->verified_at?->toISOString(),
            'notes' => $this->notes,
            'is_outside_range' => $this->params->some(fn ($p) => $p->result === VerificationResult::OutsideRange),
            'operator' => $this->whenLoaded('operator', fn () => [
                'id' => $this->operator->id,
                'name' => $this->operator->name,
            ]),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'name' => $this->equipment->name,
                'patrimony_id' => $this->equipment->patrimony_id,
            ]),
            'params' => $this->whenLoaded('params', fn () => $this->params->map(fn ($param) => [
                'id' => $param->id,
                'template_id' => $param->template_id,
                'value' => $param->value,
                'result' => $param->result?->value,
                'result_label' => $param->result_label,
                'notes' => $param->notes,
                'template' => $param->relationLoaded('template') ? [
                    'id' => $param->template->id,
                    'parameter_name' => $param->template->parameter_name,
                    'parameter_unit' => $param->template->parameter_unit,
                    'tolerance_min' => $param->template->tolerance_min,
                    'tolerance_max' => $param->template->tolerance_max,
                ] : null,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
