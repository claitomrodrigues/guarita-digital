@extends('layouts.app')

@section('title', 'Editar veículo | Guarita Digital')
@section('kicker', 'Cadastros')
@section('heading', 'Editar veículo')

@section('content')
    <article class="panel form-panel">
        <form method="POST" action="{{ route('veiculos.update', $veiculo) }}">
            @csrf
            @method('PUT')
            @include('veiculos._form')
        </form>
    </article>
@endsection
