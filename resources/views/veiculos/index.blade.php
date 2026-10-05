@extends('layouts.app')

@section('title', 'Veículos | Guarita Digital')
@section('kicker', 'Cadastros')
@section('heading', 'Veículos')

@section('content')
    <div class="view-actions">
        <form class="search-form" method="GET" action="{{ route('veiculos.index') }}">
            <input name="q" value="{{ $busca }}" placeholder="Placa, marca, modelo ou condutor">
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if ($busca !== '')
                <a class="btn btn-ghost" href="{{ route('veiculos.index') }}">Limpar</a>
            @endif
        </form>
        <a class="btn btn-primary" href="{{ route('veiculos.create') }}">+ Novo veículo</a>
    </div>

    <article class="panel">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Placa</th>
                    <th>Veículo</th>
                    <th>Tipo</th>
                    <th>Condutor</th>
                    <th>Autorização</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($veiculos as $veiculo)
                    <tr>
                        <td><strong>{{ \App\Support\Placa::formatar($veiculo->placa) }}</strong></td>
                        <td>{{ trim(($veiculo->marca ?? '').' '.$veiculo->modelo) }}<small>{{ $veiculo->cor ?: 'Cor não informada' }}</small></td>
                        <td>{{ $veiculo->tipo?->label() }}</td>
                        <td>{{ $veiculo->pessoa?->nome ?? 'Sem condutor' }}</td>
                        <td><span class="pill {{ $veiculo->autorizado ? 'success' : 'danger' }}">{{ $veiculo->autorizado ? 'Autorizado' : 'Bloqueado' }}</span></td>
                        <td class="actions">
                            <a href="{{ route('veiculos.edit', $veiculo) }}">Editar</a>
                            <form method="POST" action="{{ route('veiculos.destroy', $veiculo) }}" onsubmit="return confirm('Excluir este veículo?')">
                                @csrf
                                @method('DELETE')
                                <button class="danger-link" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Nenhum veículo cadastrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $veiculos->links() }}</div>
    </article>
@endsection
