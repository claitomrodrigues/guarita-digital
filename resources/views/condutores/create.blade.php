@extends('layouts.app')

@section('title', 'Novo condutor | Guarita Digital')
@section('kicker', 'Cadastros')
@section('heading', 'Novo condutor')

@section('content')
    <article class="panel form-panel">
        <form method="POST" action="{{ route('condutores.store') }}">
            @csrf
            @include('condutores._form')
        </form>
    </article>
@endsection
