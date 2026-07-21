<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LoanCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = LoanResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = $this->total();
        $totalActive = $this->collection->where('status', \App\Enums\LoanStatus::Active->value)->count();
        $totalOverdue = $this->collection->filter(fn ($loan) => $loan->is_overdue)->count();

        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $total,
                'summary' => [
                    'total' => $total,
                    'active_count' => $totalActive,
                    'overdue_count' => $totalOverdue,
                ],
            ],
        ];
    }
}
