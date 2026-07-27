@extends('reports.layout')

@section('content')
    @if($rows->isEmpty())
        <p style="text-align: center; color: #999; margin-top: 40px;">Nenhuma calibração encontrada para os filtros selecionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Equipamento</th>
                    <th>Status</th>
                    <th>Data Agendada</th>
                    <th>Data Conclusão</th>
                    <th>Próximo Vencimento</th>
                    <th>Responsável</th>
                    <th>Laboratório</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $calibration)
                    <tr>
                        <td>{{ $calibration->equipment?->name ?? '-' }}</td>
                        <td class="status-{{ $calibration->status->value ?? $calibration->status }}">
                            {{ $calibration->status?->label() ?? $calibration->status ?? '-' }}
                        </td>
                        <td>{{ $calibration->scheduled_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $calibration->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $calibration->next_due_at?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $calibration->responsible ?? '-' }}</td>
                        <td>{{ $calibration->laboratory ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totalizer --}}
        @php
            $byStatus = $rows->groupBy(fn($r) => $r->status?->value ?? $r->status ?? 'unknown')->map->count();
            $overdue = $rows->filter(fn($r) => $r->next_due_at?->isPast() && ($r->status?->value ?? $r->status) === 'completed')->count();
        @endphp
        <table>
            <tr class="total-row">
                <td colspan="4">Total de Calibrações: {{ $rows->count() }}</td>
                <td colspan="3">Vencidas: {{ $overdue }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="7" style="font-weight: normal; font-size: 8pt;">
                    Agendadas: {{ $byStatus->get('scheduled', 0) }} |
                    Concluídas: {{ $byStatus->get('completed', 0) }} |
                    Canceladas: {{ $byStatus->get('cancelled', 0) }}
                </td>
            </tr>
        </table>
    @endif
@endsection
