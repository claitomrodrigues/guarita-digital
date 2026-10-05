@extends('layouts.app')

@section('title', 'Dashboard | Guarita Digital')
@section('kicker', 'Monitoramento')
@section('heading', 'Dashboard de movimentação')

@section('content')
    <section class="metric-grid dashboard-metrics">
        <article class="metric">
            <span class="metric-label">Entradas hoje</span>
            <strong id="dashboard-entries">{{ number_format($totalEntradasHoje, 0, ',', '.') }}</strong>
            <span class="muted">Acessos de entrada registrados</span>
        </article>
        <article class="metric">
            <span class="metric-label">Saídas hoje</span>
            <strong id="dashboard-exits">{{ number_format($totalSaidasHoje, 0, ',', '.') }}</strong>
            <span class="muted">Acessos de saída registrados</span>
        </article>
        <article class="metric">
            <span class="metric-label">Veículos cadastrados</span>
            <strong id="dashboard-vehicles">{{ number_format($totalVeiculos, 0, ',', '.') }}</strong>
            <span class="muted"><span id="dashboard-authorized-vehicles">{{ number_format($veiculosAutorizados, 0, ',', '.') }}</span> autorizados</span>
        </article>
    </section>


    <section class="home-grid dashboard-grid">
        <article id="camera-panel" class="panel camera-panel"
                 data-recognition-url="{{ route('camera.reconhecer') }}"
                 data-manual-access-url="{{ route('acessos.registrar-manual') }}"
                 data-triage-url-template="{{ route('triagens.concluir', ['triagem' => '__TRIAGEM_ID__']) }}">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Reconhecimento</p>
                    <h2>Câmera da guarita</h2>
                </div>
                <span id="camera-status" class="pill neutral">Conectando...</span>
            </div>

            <div class="camera-stage">
                <video id="camera-video" autoplay muted playsinline aria-label="Imagem ao vivo da câmera"></video>
                <div id="camera-waiting" class="camera-waiting">
                    <span class="camera-icon">▣</span>
                    <strong>Aguardando acesso à câmera</strong>
                    <p>Autorize o navegador para mostrar a imagem.</p>
                </div>
            </div>

            <canvas id="camera-canvas" hidden></canvas>

            <div class="camera-controls">
                <button id="camera-start" class="btn btn-secondary" type="button">Ativar câmera</button>
                <button id="camera-capture" class="btn btn-primary" type="button" disabled>Tirar foto e reconhecer</button>
            </div>

            <div id="camera-result" class="camera-result" hidden aria-live="polite">
                <div>
                    <span class="camera-result-label">Resultado do reconhecimento</span>
                    <strong id="camera-plate">—</strong>
                    <p id="camera-result-message"></p>
                </div>
                <span id="camera-authorization" class="pill neutral">Resultado</span>
            </div>

            
            @can('create', \App\Models\Acesso::class)
                <div id="camera-actions" class="camera-controls" hidden>
                    <label class="camera-type-control" for="camera-access-type">Movimento
                        <select id="camera-access-type" name="tipo">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </label>
                    <button id="camera-release" class="btn btn-primary" type="button" hidden>Liberar acesso</button>
                    <button id="camera-triage" class="btn btn-secondary" type="button" hidden>Realizar triagem</button>
                </div>

                <form id="camera-triage-form" class="camera-triage-form" hidden>
                    <h3>Dados da triagem</h3>
                    <div class="form-grid">
                        <label>Nome do visitante
                            <input name="nome_visitante" maxlength="150" required>
                        </label>
                        <label>Documento
                            <input name="documento_visitante" maxlength="80">
                        </label>
                        <label>Destino
                            <input name="destino" maxlength="180" required>
                        </label>
                        <label>Decisão
                            <select name="decisao" required>
                                <option value="">Selecione</option>
                                <option value="autorizada">Autorizar entrada</option>
                                <option value="negada">Negar entrada</option>
                            </select>
                        </label>
                        <label class="field-span-2">Motivo da visita
                            <textarea name="motivo_visita" maxlength="2000" required></textarea>
                        </label>
                        <label class="field-span-2">Observações
                            <textarea name="observacoes" maxlength="2000"></textarea>
                        </label>
                    </div>
                    <p id="camera-triage-feedback" class="muted" role="status"></p>
                    <div class="form-actions">
                        <button id="camera-triage-submit" class="btn btn-primary" type="submit">Salvar triagem</button>
                    </div>
                </form>
            @endcan
        </article>

        
        <article class="panel quick-actions">
            <p class="eyebrow">Operação</p>
            <h2>Ações rápidas</h2>
            <a class="btn btn-primary" href="{{ route('condutores.create') }}">Novo condutor</a>
            <a class="btn btn-secondary" href="{{ route('veiculos.create') }}">Novo veículo</a>
            <a class="btn btn-ghost" href="{{ route('veiculos.index') }}">Ver veículos cadastrados</a>
        </article>
    </section>

    
    <article id="dashboard-yard-vehicles" class="panel"
             data-manual-access-url="{{ route('acessos.registrar-manual') }}">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Permanência</p>
                <h2>Veículos no campus</h2>
            </div>
            <span id="dashboard-yard-vehicle-count" class="pill neutral">{{ number_format(count($veiculosNoPatioLista), 0, ',', '.') }}</span>
        </div>
        <p id="dashboard-yard-feedback" class="muted" role="status" aria-live="polite"></p>
        <div class="table-wrap">
            <table class="data-table yard-table">
                <thead>
                <tr>
                    <th>Entrada</th>
                    <th>Placa</th>
                    <th>Veículo</th>
                    <th>Ação</th>
                </tr>
                </thead>
                <tbody id="dashboard-yard-vehicles-list">
                @forelse ($veiculosNoPatioLista as $veiculoNoPatio)
                    <tr>
                        <td>{{ $veiculoNoPatio['horario'] }}</td>
                        <td><strong>{{ $veiculoNoPatio['placa'] }}</strong></td>
                        <td>{{ $veiculoNoPatio['veiculo'] }}</td>
                        <td>
                            @can('create', \App\Models\Acesso::class)
                                <button class="yard-exit-button" type="button" data-plate="{{ $veiculoNoPatio['placa'] }}"
                                        aria-label="Registrar saída da placa {{ $veiculoNoPatio['placa'] }}" title="Registrar saída">X</button>
                            @else
                                —
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Nenhum veículo no pátio.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>

    
    <section class="home-grid dashboard-grid">
        <article class="panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Fluxo diário</p>
                    <h2>Entradas e saídas por horário</h2>
                </div>
                <span class="pill neutral">Hoje</span>
            </div>

            @php
                $maxEntradas = max(1, max(array_column($movimentacoesPorHora, 'entradas')));
                $maxSaidas = max(1, max(array_column($movimentacoesPorHora, 'saidas')));
            @endphp
            <div class="traffic-scroll">
                <div class="traffic-chart" id="traffic-chart" data-dashboard-url="{{ route('home') }}">
                    @foreach ($movimentacoesPorHora as $item)
                        @php
                            $entrada = (int) $item['entradas'];
                            $saida = (int) $item['saidas'];
                            $alturaEntrada = $entrada > 0 ? max(5, round($entrada / $maxEntradas * 100)) : 3;
                            $alturaSaida = $saida > 0 ? max(5, round($saida / $maxSaidas * 100)) : 3;
                        @endphp
                        <div class="traffic-column" title="{{ $item['hora'] }}: {{ $entrada }} entradas, {{ $saida }} saídas">
                            <div class="traffic-bars">
                                <span class="traffic-bar traffic-in" @style(['height' => $alturaEntrada . '%'])></span>
                                <span class="traffic-bar traffic-out" @style(['height' => $alturaSaida . '%'])></span>
                            </div>
                            <span class="traffic-label">{{ substr($item['hora'], 0, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="chart-legend">
                <span><i class="legend-dot legend-in"></i> Entradas</span>
                <span><i class="legend-dot legend-out"></i> Saídas</span>
            </div>
        </article>

        
        <article class="panel peak-panel">
            <p class="eyebrow">Horários de pico</p>
            <h2>Maior movimento</h2>
            <div class="peak-list">
                <div class="peak-item">
                    <span class="peak-icon">↗</span>
                    <div><span>Maior pico de entrada</span><strong id="dashboard-peak-entry">{{ $picoEntrada }}</strong></div>
                </div>
                <div class="peak-item">
                    <span class="peak-icon">↘</span>
                    <div><span>Maior pico de saída</span><strong id="dashboard-peak-exit">{{ $picoSaida }}</strong></div>
                </div>
                <div class="peak-item">
                    <span class="peak-icon">◎</span>
                    <div><span>Saldo atual</span><strong id="dashboard-yard-count">{{ number_format($veiculosNoPatio, 0, ',', '.') }} veículos</strong></div>
                </div>
            </div>
        </article>
    </section>

   
    <article class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Monitoramento</p>
                <h2>Últimas movimentações</h2>
            </div>
                <span id="dashboard-refresh-status" class="muted" aria-live="polite">Atualizado agora</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Horário</th>
                    <th>Placa</th>
                    <th>Veículo</th>
                    <th>Movimento</th>
                    <th>Situação</th>
                </tr>
                </thead>
                <tbody id="dashboard-recent-accesses">
                @forelse ($movimentacoesRecentes as $movimentacao)
                    <tr>
                        <td><strong>{{ $movimentacao['horario'] }}</strong></td>
                        <td>{{ $movimentacao['placa'] }}</td>
                        <td>{{ $movimentacao['veiculo'] }}</td>
                        <td>
                            <span class="pill {{ $movimentacao['tipo'] === 'entrada' ? 'success' : 'neutral' }}">
                                {{ $movimentacao['tipo'] === 'entrada' ? 'Entrada' : 'Saída' }}
                            </span>
                        </td>
                        <td>
                            <span class="pill {{ $movimentacao['autorizado'] ? 'success' : 'danger' }}">
                                {{ $movimentacao['autorizado'] ? 'Autorizado' : 'Pendente ou bloqueado' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Nenhuma movimentação registrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection

@push('scripts')
    <script src="{{ asset('js/home-dashboard.js') }}" defer></script>
    <script src="{{ asset('js/home-camera.js') }}" defer></script>
@endpush
