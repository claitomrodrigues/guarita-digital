@extends('layouts.app')

@section('title', 'Condutores | Guarita Digital')
@section('kicker', 'Cadastros')
@section('heading', 'Condutores')

@section('content')
    <div class="view-actions">
        <form class="search-form" method="GET" action="{{ route('condutores.index') }}">
            <input name="q" value="{{ $busca }}" placeholder="Nome ou e-mail">
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if ($busca !== '')
                <a class="btn btn-ghost" href="{{ route('condutores.index') }}">Limpar</a>
            @endif
        </form>
        <a class="btn btn-primary" href="{{ route('condutores.create') }}">+ Novo condutor</a>
    </div>

    <article class="panel">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Vínculo</th>
                    <th>Contato</th>
                    <th>Veículos</th>
                    <th>Situação</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($condutores as $condutor)
                    <tr>
                        <td><strong>{{ $condutor->nome }}</strong></td>
                        <td>{{ $condutor->tipo_vinculo?->label() }}</td>
                        <td>{{ $condutor->email ?: ($condutor->telefone ?: 'Não informado') }}</td>
                        <td>{{ $condutor->veiculos_count }}</td>
                        <td><span class="pill {{ $condutor->ativo ? 'success' : 'danger' }}">{{ $condutor->ativo ? 'Ativo' : 'Inativo' }}</span></td>
                        <td class="actions">
                            <a href="{{ route('condutores.edit', $condutor) }}">Editar</a>
                            <form method="POST" action="{{ route('condutores.destroy', $condutor) }}" onsubmit="return confirm('Excluir este condutor?')">
                                @csrf
                                @method('DELETE')
                                <button class="danger-link" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Nenhum condutor cadastrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $condutores->links() }}</div>
    </article>
@endsection
