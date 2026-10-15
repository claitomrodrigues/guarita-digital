<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px; }
        body { color: #17211d; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        h2 { margin: 0 0 10px; font-size: 12px; }
        p { margin: 0 0 12px; color: #65716c; }
        .header { border-bottom: 2px solid #087f5b; margin-bottom: 14px; padding-bottom: 10px; }
        .summary { border-collapse: separate; border-spacing: 6px; margin: 0 -6px 12px; width: 100%; }
        .summary td { background: #f1f7f4; padding: 9px; width: 25%; }
        .summary strong { display: block; color: #087f5b; font-size: 15px; margin-top: 4px; }
        .charts { border-collapse: separate; border-spacing: 6px; margin: 0 -6px 14px; width: 100%; }
        .charts td { border: 1px solid #dfe7e3; padding: 8px; vertical-align: top; width: 33.33%; }
        .chart-row { margin: 5px 0; }
        .chart-label { display: inline-block; width: 95px; }
        .track { background: #e7f0ec; display: inline-block; height: 7px; width: 150px; }
        .bar { background: #087f5b; display: block; height: 7px; }
        .timeline { border-collapse: collapse; width: 100%; }
        .timeline td { border: 0; padding: 3px 0; }
        .timeline-label { width: 44px; }
        .timeline-track { background: #e7f0ec; height: 7px; font-size: 0; white-space: nowrap; }
        .timeline-in, .timeline-out { display: inline-block; height: 7px; vertical-align: top; }
        .timeline-in { background: #087f5b; }
        .timeline-out { background: #d28b2c; }
        .timeline-total { text-align: right; width: 32px; }
        .filters { margin-bottom: 12px; }
        table.accesses { border-collapse: collapse; width: 100%; }
        .accesses th { background: #0e2820; color: #fff; font-size: 8px; padding: 6px 5px; text-align: left; }
        .accesses td { border-bottom: 1px solid #dfe7e3; padding: 5px; }
        .accesses tr { page-break-inside: avoid; }
        .muted { color: #65716c; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Guarita Digital — Relatório de acessos</h1>
        <p>Período de {{ \Carbon\CarbonImmutable::parse($filtros['data_inicio'])->format('d/m/Y') }} a {{ \Carbon\CarbonImmutable::parse($filtros['data_fim'])->format('d/m/Y') }}
            &nbsp; • &nbsp; Gerado em {{ now()->format('d/m/Y H:i') }}</p>
    </header>

    <table class="summary">
        <tr>
            <td>Total de acessos<strong>{{ number_format($totalAcessos, 0, ',', '.') }}</strong></td>
            <td>Entradas<strong>{{ number_format($totalEntradas, 0, ',', '.') }}</strong></td>
            <td>Saídas<strong>{{ number_format($totalSaidas, 0, ',', '.') }}</strong></td>
            <td>Autorizados<strong>{{ number_format($totalAutorizados, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="charts">
        <tr>
            <td>
                <h2>Movimentações no período</h2>
                @php($maxMovimento = max(1, ...array_column($movimentos, 'total')))
                <table class="timeline">
                    @foreach ($movimentos as $movimento)
                        <tr>
                            <td class="timeline-label">{{ $movimento['label'] }}</td>
                            <td>
                                <div class="timeline-track">
                                    <span class="timeline-in" style="width: {{ round(($movimento['entrada'] ?? 0) / $maxMovimento * 100) }}%"></span><span class="timeline-out" style="width: {{ round(($movimento['saida'] ?? 0) / $maxMovimento * 100) }}%"></span>
                                </div>
                            </td>
                            <td class="timeline-total">{{ $movimento['total'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td>
                <h2>Acessos por status</h2>
                @foreach ($porStatus as $item)
                    <div class="chart-row">
                        <span class="chart-label">{{ $item['label'] }}</span>
                        <span class="track"><span class="bar" style="width: {{ max(3, $item['percentual']) }}%"></span></span>
                        <strong>{{ $item['total'] }}</strong>
                    </div>
                @endforeach
            </td>
            <td>
                <h2>Acessos por origem</h2>
                @foreach ($porOrigem as $item)
                    <div class="chart-row">
                        <span class="chart-label">{{ $item['label'] }}</span>
                        <span class="track"><span class="bar" style="width: {{ max(3, $item['percentual']) }}%"></span></span>
                        <strong>{{ $item['total'] }}</strong>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <div class="filters">
        <strong>Filtros aplicados:</strong>
        {{ filled($filtros['placa'] ?? null) ? 'Placa '.\App\Support\Placa::formatar($filtros['placa']) : 'Todas as placas' }}
        • {{ isset($filtros['tipo']) ? \App\Enums\TipoAcesso::from($filtros['tipo'])->label() : 'Entradas e saídas' }}
        • {{ isset($filtros['status']) ? \App\Enums\StatusAcesso::from($filtros['status'])->label() : 'Todos os status' }}
        • {{ isset($filtros['origem']) ? \App\Enums\OrigemAcesso::from($filtros['origem'])->label() : 'Todas as origens' }}
        • {{ isset($filtros['ponto_acesso_id']) ? 'Ponto de acesso #'.$filtros['ponto_acesso_id'] : 'Todos os pontos de acesso' }}
    </div>

    <h2>Detalhamento das movimentações ({{ number_format($totalAcessos, 0, ',', '.') }})</h2>
    <table class="accesses">
        <thead>
        <tr>
            <th>Data e hora</th>
            <th>Placa</th>
            <th>Movimento</th>
            <th>Status</th>
            <th>Pessoa</th>
            <th>Ponto de acesso</th>
            <th>Origem</th>
            <th>Confiança OCR</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($acessos as $acesso)
            <tr>
                <td>{{ $acesso->data_hora?->format('d/m/Y H:i:s') ?? '—' }}</td>
                <td>{{ \App\Support\Placa::formatar($acesso->placa_reconhecida) }}</td>
                <td>{{ $acesso->tipo?->label() ?? '—' }}</td>
                <td>{{ $acesso->status?->label() ?? '—' }}</td>
                <td>{{ $acesso->veiculo?->pessoa?->nome ?? $acesso->pessoa?->nome ?? '—' }}</td>
                <td>{{ $acesso->pontoAcesso?->nome ?? '—' }}</td>
                <td>{{ $acesso->origem?->label() ?? '—' }}</td>
                <td>{{ $acesso->confianca !== null ? $acesso->confianca.'%' : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">Nenhuma movimentação encontrada com os filtros selecionados.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
