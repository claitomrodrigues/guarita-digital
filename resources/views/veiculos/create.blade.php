@extends('layouts.app')

@section('title', 'Novo veículo | Guarita Digital')
@section('kicker', 'Cadastros')
@section('heading', 'Novo veículo')

@section('content')
    <article class="panel form-panel">
        <form method="POST" action="{{ route('veiculos.store') }}">
            @csrf
            @include('veiculos._form')
        </form>
    </article>
@endsection
