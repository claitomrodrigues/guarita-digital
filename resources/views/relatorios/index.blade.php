@extends('layouts.app')

@section('title', 'Relatórios | Guarita Digital')
@section('kicker', 'Análise de movimentações')
@section('heading', 'Relatório de acessos')

@section('content')
    <section class="panel report-filters">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Filtros</p>
                <h2>Período e movimentações</h2>
            </div>
            <div class="report-downloads">
                <a class="btn btn-secondary" href="{{ route('relatorios.csv', $filtros) }}">Baixar CSV</a>
                <a class="btn btn-primary" href="{{ route('relatorios.pdf', $filtros) }}">Baixar relatório PDF</a>
            </div>
        </div>

        <form method="GET" action="{{ route('relatorios.index') }}">
            <div class="form-grid report-filter-grid">
                <label>Data inicial
                    <input type="date" name="data_inicio" value="{{ $filtros['data_inicio'] }}" required>
                </label>
                <label>Data final
                    <input type="date" name="data_fim" value="{{ $filtros['data_fim'] }}" required>
                </label>
                <label>Placa
                    <input name="placa" value="{{ $filtros['placa'] ?? '' }}" maxlength="10" placeholder="Ex.: ABC1D23">
                </label>
                <label>Movimento
                    <select name="tipo">
                        <option value="">Entradas e saídas</option>
                        <option value="entrada" @selected(($filtros['tipo'] ?? '') === 'entrada')>Entrada</option>
                        <option value="saida" @selected(($filtros['tipo'] ?? '') === 'saida')>Saída</option>
                    </select>
                </label>
                <label>Status
                    <select name="status">
                        <option value="">Todos os status</option>
                        @foreach (\App\Enums\StatusAcesso::opcoes() as $opcao)
                            <option value="{{ $opcao['value'] }}" @selected(($filtros['status'] ?? '') === $opcao['value'])>{{ $opcao['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Origem
                    <select name="origem">
                        <option value="">Todas as origens</option>
                        @foreach (\App\Enums\OrigemAcesso::opcoes() as $opcao)
                            <option value="{{ $opcao['value'] }}" @selected(($filtros['origem'] ?? '') === $opcao['value'])>{{ $opcao['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Ponto de acesso
                    <select name="ponto_acesso_id">
                        <option value="">Todos os pontos</option>
                        @foreach ($pontosAcesso as $ponto)
                            <option value="{{ $ponto->id }}" @selected((string) ($filtros['ponto_acesso_id'] ?? '') === (string) $ponto->id)>{{ $ponto->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="report-filter-actions">
                    <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                </div>
            </div>
        </form>
    </section>

    <section class="metric-grid report-metrics" aria-label="Resumo do período">
        <article class="metric">
            <span class="metric-label">Acessos registrados</span>
            <strong>{{ number_format($totalAcessos, 0, ',', '.') }}</strong>
            <span class="muted">No período selecionado</span>
        </article>
        <article class="metric">
            <span class="metric-label">Entradas</span>
            <strong>{{ number_format($totalEntradas, 0, ',', '.') }}</strong>
            <span class="muted">Movimentações de entrada</span>
        </article>
        <article class="metric">
            <span class="metric-label">Saídas</span>
            <strong>{{ number_format($totalSaidas, 0, ',', '.') }}</strong>
            <span class="muted">Movimentações de saída</span>
        </article>
        <article class="metric">
            <span class="metric-label">Autorizações</span>
            <strong>{{ number_format($totalAutorizados, 0, ',', '.') }}</strong>
            <span class="muted">{{ number_format($totalNegados, 0, ',', '.') }} pendentes ou não autorizados</span>
        </article>
    </section>

    <section class="report-chart-grid">
        <article class="panel report-chart-panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Tendência</p>
                    <h2>Movimentações no período</h2>
                </div>
                <span class="muted">{{ $filtros['data_inicio'] }} — {{ $filtros['data_fim'] }}</span>
            </div>
            @php($maxMovimento = max(1, ...array_column($movimentos, 'total')))
            <div class="report-timeline">
                @forelse ($movimentos as $movimento)
                    <div class="timeline-item" title="{{ $movimento['label'] }}: {{ $movimento['total'] }} acessos">
                        <span class="timeline-label">{{ $movimento['label'] }}</span>
                        <div class="timeline-track">
                            <span class="timeline-bar timeline-in" @style(['width' => round(($movimento['entrada'] ?? 0) / $maxMovimento * 100) . '%'])></span>
                            <span class="timeline-bar timeline-out" @style(['width' => round(($movimento['saida'] ?? 0) / $maxMovimento * 100) . '%'])></span>
                        </div>
                        <strong>{{ number_format($movimento['total'], 0, ',', '.') }}</strong>
                    </div>
                @empty
                    <p class="empty">Sem movimentações para o período.</p>
                @endforelse
            </div>
            <div class="chart-legend">
                <span><i class="legend-dot legend-in"></i> Entradas</span>
                <span><i class="legend-dot legend-out"></i> Saídas</span>
            </div>
        </article>

        <article class="panel report-chart-panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Distribuição</p>
                    <h2>Acessos por status</h2>
                </div>
            </div>
            <div class="report-breakdown">
                @forelse ($porStatus as $item)
                    <div class="breakdown-item">
                        <div class="breakdown-label"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['total'], 0, ',', '.') }}</strong></div>
                        <div class="breakdown-track"><span @style(['width' => max(3, $item['percentual']) . '%'])></span></div>
                    </div>
                @empty
                    <p class="empty">Sem dados para exibir.</p>
                @endforelse
            </div>
        </article>

        <article class="panel report-chart-panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Distribuição</p>
                    <h2>Acessos por origem</h2>
                </div>
            </div>
            <div class="report-breakdown">
                @forelse ($porOrigem as $item)
                    <div class="breakdown-item">
                        <div class="breakdown-label"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['total'], 0, ',', '.') }}</strong></div>
                        <div class="breakdown-track"><span @style(['width' => max(3, $item['percentual']) . '%'])></span></div>
                    </div>
                @empty
                    <p class="empty">Sem dados para exibir.</p>
                @endforelse
            </div>
        </article>
    </section>

    <article class="panel report-accesses">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Detalhamento</p>
                <h2>Movimentações encontradas</h2>
            </div>
            <span class="pill neutral">{{ number_format($acessos->total(), 0, ',', '.') }} registros</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Data e hora</th>
                    <th>Placa</th>
                    <th>Movimento</th>
                    <th>Pessoa</th>
                    <th>Ponto de acesso</th>
                    <th>Origem</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($acessos as $acesso)
                    <tr>
                        <td>{{ $acesso->data_hora?->format('d/m/Y H:i:s') ?? '—' }}</td>
                        <td><strong>{{ \App\Support\Placa::formatar($acesso->placa_reconhecida) }}</strong></td>
                        <td>{{ $acesso->tipo?->label() ?? '—' }}</td>
                        <td>{{ $acesso->veiculo?->pessoa?->nome ?? $acesso->pessoa?->nome ?? '—' }}</td>
                        <td>{{ $acesso->pontoAcesso?->nome ?? '—' }}</td>
                        <td>{{ $acesso->origem?->label() ?? '—' }}</td>
                        <td>{{ $acesso->status?->label() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Nenhuma movimentação encontrada com esses filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="report-pagination">{{ $acessos->links() }}</div>
    </article>
@endsection
