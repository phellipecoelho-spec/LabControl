@extends('reports.layout')

@section('content')
    @if($rows->isEmpty())
        <p style="text-align: center; color: #999; margin-top: 40px;">Nenhuma movimentação encontrada para os filtros selecionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Saldo após</th>
                    <th>Motivo</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $movement)
                    <tr>
                        <td>{{ $movement->item?->name ?? '-' }}</td>
                        <td>{{ $movement->item?->code ?? '-' }}</td>
                        <td>{{ ucfirst($movement->type) }}</td>
                        <td style="text-align: right;">{{ number_format($movement->quantity, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($movement->balance_after, 0, ',', '.') }}</td>
                        <td>{{ $movement->reason ?? '-' }}</td>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totalizer --}}
        @php
            $byType = $rows->groupBy('type')->map(fn($g) => $g->sum('quantity'));
            $incoming = $byType->get('purchase', 0) + $byType->get('return', 0);
            $outgoing = $byType->get('consumption', 0) + $byType->get('adjustment', 0) + $byType->get('disposal', 0);
        @endphp
        <table>
            <tr class="total-row">
                <td colspan="4">Total de Movimentações: {{ $rows->count() }}</td>
                <td colspan="3">Variação líquida: {{ number_format($incoming - $outgoing, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="7" style="font-weight: normal; font-size: 8pt;">
                    Entradas (Compras): {{ number_format($byType->get('purchase', 0), 0, ',', '.') }} |
                    Consumos: {{ number_format($byType->get('consumption', 0), 0, ',', '.') }} |
                    Ajustes: {{ number_format($byType->get('adjustment', 0), 0, ',', '.') }} |
                    Devoluções: {{ number_format($byType->get('return', 0), 0, ',', '.') }} |
                    Descarte: {{ number_format($byType->get('disposal', 0), 0, ',', '.') }}
                </td>
            </tr>
        </table>
    @endif
@endsection
