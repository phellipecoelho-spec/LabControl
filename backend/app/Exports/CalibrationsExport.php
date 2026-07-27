<?php

namespace App\Exports;

use App\Models\Calibration;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CalibrationsExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private ?string $status = null,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
    ) {}

    public function array(): array
    {
        return Calibration::with(['equipment'])
            ->when($this->status, fn($q, $v) => $q->where('status', $v))
            ->when($this->dateFrom, fn($q, $v) => $q->where('scheduled_date', '>=', Carbon::parse($v)))
            ->when($this->dateTo, fn($q, $v) => $q->where('scheduled_date', '<=', Carbon::parse($v)))
            ->get()
            ->map(fn(Calibration $cal) => [
                $cal->equipment?->name ?? '-',
                $cal->status?->label() ?? $cal->status ?? '-',
                $cal->scheduled_date?->format('d/m/Y') ?? '-',
                $cal->completed_at?->format('d/m/Y H:i') ?? '-',
                $cal->next_due_at?->format('d/m/Y') ?? '-',
                $cal->responsible ?? '-',
                $cal->laboratory ?? '-',
            ])
            ->toArray();
    }

    public function headings(): array
    {
        return ['Equipamento', 'Status', 'Data Agendada', 'Data Conclusão', 'Próximo Vencimento', 'Responsável', 'Laboratório'];
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
