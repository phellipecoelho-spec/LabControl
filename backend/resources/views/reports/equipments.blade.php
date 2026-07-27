@extends('reports.layout')

@section('content')
    @if($rows->isEmpty())
        <p style="text-align: center; color: #999; margin-top: 40px;">Nenhum equipamento encontrado para os filtros selecionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Patrimônio</th>
                    <th>Nº Série</th>
                    <th>Categoria</th>
                    <th>Fabricante</th>
                    <th>Status</th>
                    <th>Localização</th>
                    <th>Data Aquisição</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $equipment)
                    <tr>
                        <td>{{ $equipment->name }}</td>
                        <td>{{ $equipment->patrimony_id ?? '-' }}</td>
                        <td>{{ $equipment->serial_number ?? '-' }}</td>
                        <td>{{ $equipment->category?->name ?? '-' }}</td>
                        <td>{{ $equipment->manufacturer?->name ?? '-' }}</td>
                        <td class="status-{{ $equipment->status }}">{{ ucfirst($equipment->status) }}</td>
                        <td>{{ $equipment->location ?? '-' }}</td>
                        <td>{{ $equipment->acquisition_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totalizer --}}
        <table>
            <tr class="total-row">
                <td colspan="7">Total de Equipamentos: {{ $rows->count() }}</td>
                <td>
                    @php
                        $byStatus = $rows->groupBy('status')->map->count();
                    @endphp
                    Ativos: {{ $byStatus->get('active', 0) }}
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="8" style="font-weight: normal; font-size: 8pt;">
                    Inativos: {{ $byStatus->get('inactive', 0) }} |
                    Manutenção: {{ $byStatus->get('maintenance', 0) }} |
                    Baixados: {{ $byStatus->get('retired', 0) }}
                </td>
            </tr>
        </table>
    @endif
@endsection
