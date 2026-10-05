<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Guarita Digital')</title>
    <link rel="stylesheet" href="{{ asset('css/guarita.css') }}">
</head>
<body class="app-page">
<aside class="sidebar">
    <a class="sidebar-brand" href="{{ route('home') }}">
        <span class="brand-mark small">GD</span>
        <span><strong>Guarita Digital</strong><small>IFFar • SVS</small></span>
    </a>

    <nav aria-label="Navegação principal">
        <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
            <span class="nav-icon">⌂</span> Home
        </a>
        <a class="{{ request()->routeIs('condutores.*') ? 'active' : '' }}" href="{{ route('condutores.index') }}">
            <span class="nav-icon">♙</span> Condutores
        </a>
        <a class="{{ request()->routeIs('veiculos.*') ? 'active' : '' }}" href="{{ route('veiculos.index') }}">
            <span class="nav-icon">▱</span> Veículos
        </a>
    </nav>

    <div class="sidebar-note">
        <strong>Versão 1.3</strong>
    </div>
</aside>

<main class="app-main">
    <header class="topbar">
        <div>
            <p class="eyebrow">@yield('kicker', 'Guarita Digital')</p>
            <h1>@yield('heading', 'Home')</h1>
        </div>
        <div class="topbar-actions">
            <div class="user-chip">
                <span>{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->perfil?->label() ?? 'Usuário' }}</small>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Sair</button>
            </form>
        </div>
    </header>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            <strong>Confira os dados informados:</strong>
            <ul>
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>
@stack('scripts')
</body>
</html>
