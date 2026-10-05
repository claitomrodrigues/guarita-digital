@extends('layouts.app')

@section('title', 'Editar condutor | Guarita Digital')
@section('kicker', 'Cadastros')
@section('heading', 'Editar condutor')

@section('content')
    <article class="panel form-panel">
        <form method="POST" action="{{ route('condutores.update', $condutor) }}">
            @csrf
            @method('PUT')
            @include('condutores._form')
        </form>
    </article>
@endsection
