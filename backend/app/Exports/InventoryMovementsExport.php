<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryMovementsExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private ?string $type = null,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
    ) {}

    public function array(): array
    {
        return InventoryMovement::with(['item', 'user'])
            ->when($this->type, fn($q, $v) => $q->where('type', $v))
            ->when($this->dateFrom, fn($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)))
            ->when($this->dateTo, fn($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->get()
            ->map(fn(InventoryMovement $mov) => [
                $mov->item?->name ?? '-',
                $mov->item?->code ?? '-',
                $mov->type,
                $mov->quantity,
                $mov->balance_after,
                $mov->reason ?? '-',
                $mov->user?->name ?? '-',
                $mov->created_at->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    public function headings(): array
    {
        return ['Item', 'Código', 'Tipo', 'Quantidade', 'Saldo após', 'Motivo', 'Usuário', 'Data'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
        ];
    }
}
