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
<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>
<aside class="sidebar">
    <a class="sidebar-brand" href="{{ route('home') }}">
        <span class="brand-mark small" aria-hidden="true">
            <svg viewBox="0 0 32 32" focusable="false">
                <path d="M16 3 27 7v8c0 7-4.5 11.5-11 14C9.5 26.5 5 22 5 15V7l11-4Z" />
                <path d="m11 16 3.3 3.3L21 12.5" />
            </svg>
        </span>
        <span><strong>Guarita Digital</strong><small>IFFar <i></i> SVS</small></span>
    </a>

    <nav aria-label="Navegação principal">
        <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
            <span class="nav-icon" aria-hidden="true">⌂</span><span>Visão geral</span>
        </a>
        <a class="{{ request()->routeIs('condutores.*') ? 'active' : '' }}" href="{{ route('condutores.index') }}">
            <span class="nav-icon" aria-hidden="true">♙</span><span>Condutores</span>
        </a>
        <a class="{{ request()->routeIs('veiculos.*') ? 'active' : '' }}" href="{{ route('veiculos.index') }}">
            <span class="nav-icon" aria-hidden="true">▱</span><span>Veículos</span>
        </a>
        <a class="{{ request()->routeIs('relatorios.*') ? 'active' : '' }}" href="{{ route('relatorios.index') }}">
            <span class="nav-icon" aria-hidden="true">▤</span><span>Relatórios</span>
        </a>
    </nav>

    <div class="sidebar-status">
        <span class="status-indicator"></span>
        <div><strong>Posto operacional</strong><small>Sistema pronto para uso</small></div>
    </div>
    <div class="sidebar-note">
        <strong>Guarita Digital <span>•</span> v2.0</strong>
        <small>IFFar — Campus São Vicente do Sul</small>
    </div>
</aside>

<main class="app-main" id="conteudo-principal">
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
                    <small><i class="user-status-dot"></i>{{ auth()->user()->perfil?->label() ?? 'Operador' }}</small>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost logout-button" type="submit"><span aria-hidden="true">↗</span> Sair</button>
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
