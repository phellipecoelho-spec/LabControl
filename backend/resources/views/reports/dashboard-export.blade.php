@extends('reports.layout')

@section('content')
    <div class="section-title">Indicadores (KPIs)</div>

    <table class="kpi-grid">
        <tr>
            @foreach($kpis as $label => $value)
                <td>
                    <div class="kpi-value">{{ $value }}</div>
                    <div class="kpi-label">{{ $label }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="section-title">Equipamentos por Categoria</div>
    @if(!empty($equipmentsByCategory))
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipmentsByCategory as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td style="text-align: right;">{{ $item['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #999;">Nenhum dado disponível.</p>
    @endif

    <div class="section-title">Calibrações por Mês</div>
    @if(!empty($calibrationsTimeline))
        <table>
            <thead>
                <tr>
                    <th>Mês</th>
                    <th>Agendadas</th>
                    <th>Concluídas</th>
                    <th>Vencendo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calibrationsTimeline as $item)
                    <tr>
                        <td>{{ $item['month'] }}</td>
                        <td style="text-align: right;">{{ $item['scheduled'] }}</td>
                        <td style="text-align: right;">{{ $item['completed'] }}</td>
                        <td style="text-align: right;">{{ $item['due'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #999;">Nenhum dado disponível.</p>
    @endif

    <div class="section-title">Movimentações de Estoque</div>
    @if(!empty($stockMovements))
        <table>
            <thead>
                <tr>
                    <th>Mês</th>
                    <th>Entradas</th>
                    <th>Saídas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockMovements as $item)
                    <tr>
                        <td>{{ $item['month'] }}</td>
                        <td style="text-align: right;">{{ $item['incoming'] }}</td>
                        <td style="text-align: right;">{{ $item['outgoing'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #999;">Nenhum dado disponível.</p>
    @endif

    <div class="legend">
        <p>Fonte: LabControl — Dados do painel. Período baseado nos filtros aplicados.</p>
    </div>
@endsection
