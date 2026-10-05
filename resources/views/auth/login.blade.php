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
        <div class="brand-mark">GD</div>
        <p class="eyebrow">IFFar • Campus São Vicente do Sul</p>
        <h1>Guarita Digital</h1>
        <p>Controle dos condutores e veículos cadastrados no campus.</p>
    </section>

    <section class="login-card">
        <p class="eyebrow">Acesso ao sistema</p>
        <h2>Entre na sua conta</h2>
        <p class="muted">Utilize as credenciais cadastradas.</p>

        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="form-stack">
            @csrf
            <label>
                Matrícula ou e-mail
                <input name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
            </label>
            <label>
                Senha
                <input name="password" type="password" autocomplete="current-password" required>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Entrar</button>
        </form>
    </section>
</main>
</body>
</html>
