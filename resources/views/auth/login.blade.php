<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar | Guarita Digital</title>
    <link rel="stylesheet" href="{{ asset('css/guarita.css') }}">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-brand">
        <a class="login-brand-mark" href="{{ route('login') }}" aria-label="Guarita Digital">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" focusable="false">
                    <path d="M16 3 27 7v8c0 7-4.5 11.5-11 14C9.5 26.5 5 22 5 15V7l11-4Z" />
                    <path d="m11 16 3.3 3.3L21 12.5" />
                </svg>
            </span>
            <span>IFFar <i></i> SVS</span>
        </a>
        <p class="eyebrow">Controle de acesso <span></span> Campus São Vicente do Sul</p>
        <h1>Guarita<br><span>Digital</span></h1>
        <p>Uma guarita mais ágil, organizada e segura para acompanhar cada chegada e saída.</p>ft
    </section>

    <section class="login-card">
        <div class="login-card-icon" aria-hidden="true"><span>↗</span></div>
        <p class="eyebrow">Área do operador</p>
        <h2>Bem-vindo à guarita</h2>
        <p class="muted">Entre com as credenciais da conta operacional.</p>

        @if ($errors->any())
            <div class="alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="form-stack">
            @csrf
            <label>
                Matrícula ou e-mail
                <span class="login-input-wrap">
                    <span aria-hidden="true">◎</span>
                    <input name="login" value="{{ old('login') }}" autocomplete="username" placeholder="Digite sua matrícula ou e-mail" required autofocus>
                </span>
            </label>
            <label>
                Senha
                <span class="login-input-wrap">
                    <span aria-hidden="true">⌑</span>
                    <input name="password" type="password" autocomplete="current-password" placeholder="Digite sua senha" required>
                </span>
            </label>
            <button class="btn btn-primary btn-block login-submit" type="submit"><span>Entrar no sistema</span><span aria-hidden="true">→</span></button>
        </form>
        <div class="login-security-note"><span aria-hidden="true">✓</span><span>Acesso restrito ao operador da guarita</span></div>
        <p class="login-footer">IFFar — Campus São Vicente do Sul <span>•</span> Guarita Digital</p>
    </section>
</main>
</body>
</html>
