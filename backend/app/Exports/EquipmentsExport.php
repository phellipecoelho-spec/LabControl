<?php

namespace App\Exports;

use App\Models\Equipment;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EquipmentsExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private ?string $status = null,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
    ) {}

    public function array(): array
    {
        return Equipment::with(['category', 'manufacturer', 'supplier'])
            ->when($this->status, fn($q, $v) => $q->where('status', $v))
            ->when($this->dateFrom, fn($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)))
            ->when($this->dateTo, fn($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->get()
            ->map(fn(Equipment $eq) => [
                $eq->name,
                $eq->patrimony_id ?? '-',
                $eq->serial_number ?? '-',
                $eq->category?->name ?? '-',
                $eq->manufacturer?->name ?? '-',
                $eq->status,
                $eq->location ?? '-',
                $eq->acquisition_date?->format('d/m/Y') ?? '-',
            ])
            ->toArray();
    }

    public function headings(): array
    {
        return ['Nome', 'Patrimônio', 'Nº Série', 'Categoria', 'Fabricante', 'Status', 'Localização', 'Data Aquisição'];
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
