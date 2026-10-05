<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guarita Digital</title>
    <link rel="stylesheet" href="{{ asset('css/guarita.css') }}">
    <link rel="stylesheet" href="{{ asset('css/permissions.css') }}">
</head>
<body class="app-page">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-mark small">GD</div><div><strong>Guarita Digital</strong><small>IFFar • SVS</small></div></div>
    <nav id="main-nav" aria-label="Navegação principal">
        <button data-view="dashboard" class="active">▦ <span>Dashboard</span></button>
        <button data-view="movimentacoes">⇄ <span>Movimentações</span></button>
        <button data-view="triagens">⌕ <span>Triagens</span><b id="nav-triagens" class="badge" hidden>0</b></button>
        <button data-view="pessoas">♙ <span>Pessoas</span></button>
        <button data-view="veiculos">▱ <span>Veículos</span></button>
        <button data-view="pontos">⌾ <span>Pontos de acesso</span></button>
        <button data-view="usuarios" data-admin>♟ <span>Usuários</span></button>
        <button data-view="relatorios">▤ <span>Relatórios</span></button>
        <button data-view="auditoria" data-admin>◫ <span>Auditoria</span></button>
        <button data-view="configuracoes" data-admin>⚙ <span>Configurações</span></button>
    </nav>
    <div class="sidebar-footer"><span id="camera-summary">Verificando câmeras…</span></div>
</aside>

<main class="app-main">
    <header class="topbar">
        <button id="menu-button" class="icon-button" aria-label="Abrir menu">☰</button>
        <div><p class="eyebrow" id="page-kicker">Visão geral</p><h1 id="page-title">Dashboard</h1></div>
        <div class="topbar-actions">
            <button id="refresh-button" class="icon-button" title="Atualizar">↻</button>
            <div class="user-chip"><span id="user-initials">--</span><div><strong id="user-name">Carregando…</strong><small id="user-role"></small></div></div>
            <button id="logout-button" class="btn btn-ghost">Sair</button>
        </div>
    </header>

    <section id="view-dashboard" class="view active">
        <div id="summary-cards" class="metric-grid"></div>
        <div class="content-grid two-one">
            <article class="panel"><div class="panel-head"><div><p class="eyebrow">Últimos 7 dias</p><h2>Fluxo de veículos</h2></div></div><div id="flow-chart" class="bar-chart"></div></article>
            <article class="panel"><div class="panel-head"><div><p class="eyebrow">Agora</p><h2>Situação operacional</h2></div></div><div id="operation-status" class="status-list"></div></article>
        </div>
        <article class="panel"><div class="panel-head"><div><p class="eyebrow">Atualização contínua</p><h2>Últimas movimentações</h2></div><button class="btn btn-secondary" data-go="movimentacoes">Ver todas</button></div><div id="recent-accesses" class="table-wrap"></div></article>
    </section>

    <section id="view-movimentacoes" class="view"><div class="view-actions"><div class="filters"><input id="access-plate" placeholder="Buscar placa"><select id="access-type"><option value="">Entrada e saída</option><option value="entrada">Entradas</option><option value="saida">Saídas</option></select><select id="access-status"><option value="">Todos os status</option></select><button class="btn btn-secondary" data-action="filter-access">Filtrar</button></div><button class="btn btn-primary" data-action="manual-access">+ Registro manual</button></div><article class="panel"><div id="access-table" class="table-wrap"></div><div id="access-pagination" class="pagination"></div></article></section>

    <section id="view-triagens" class="view"><div class="view-actions"><div class="filters"><select id="screening-status"><option value="">Todas</option></select><input id="screening-search" placeholder="Placa, visitante ou destino"><button class="btn btn-secondary" data-action="filter-screenings">Filtrar</button></div></div><article class="panel"><div id="screening-table" class="table-wrap"></div></article></section>

    <section id="view-pessoas" class="view"><div class="view-actions"><div class="filters"><input id="people-search" placeholder="Nome, matrícula, CPF ou e-mail"><select id="people-link"><option value="">Todos os vínculos</option></select><button class="btn btn-secondary" data-action="filter-people">Filtrar</button></div><button class="btn btn-primary" data-action="new-person" data-admin>+ Nova pessoa</button></div><article class="panel"><div id="people-table" class="table-wrap"></div></article></section>

    <section id="view-veiculos" class="view"><div class="view-actions"><div class="filters"><input id="vehicle-search" placeholder="Placa, modelo ou proprietário"><select id="vehicle-type"><option value="">Todos os tipos</option></select><select id="vehicle-authorized"><option value="">Todas as situações</option><option value="1">Autorizados</option><option value="0">Bloqueados</option></select><button class="btn btn-secondary" data-action="filter-vehicles">Filtrar</button></div><button class="btn btn-primary" data-action="new-vehicle" data-admin>+ Novo veículo</button></div><article class="panel"><div id="vehicle-table" class="table-wrap"></div></article></section>

    <section id="view-pontos" class="view"><div class="view-actions"><p class="muted">Gerencie guaritas, sentidos e tokens de integração.</p><button class="btn btn-primary" data-action="new-point" data-admin>+ Novo ponto</button></div><div id="points-grid" class="card-grid"></div></section>

    <section id="view-usuarios" class="view"><div class="view-actions"><p class="muted">Controle os acessos administrativos e da equipe de segurança.</p><button class="btn btn-primary" data-action="new-user">+ Novo usuário</button></div><article class="panel"><div id="users-table" class="table-wrap"></div></article></section>

    <section id="view-relatorios" class="view"><div class="report-grid"><article class="panel report-card"><p class="eyebrow">CSV</p><h2>Relatório de acessos</h2><p>Exporte movimentações filtradas por período, placa, direção e status.</p><div class="form-grid"><label>Data inicial<input id="report-start" type="date"></label><label>Data final<input id="report-end" type="date"></label><label>Placa<input id="report-plate" maxlength="8"></label><label>Status<select id="report-status"><option value="">Todos</option></select></label></div><button class="btn btn-primary" data-action="export-access">Exportar CSV</button></article><article class="panel report-card"><p class="eyebrow">Resumo</p><h2>Indicadores do dia</h2><div id="report-summary" class="status-list"></div></article></div></section>

    <section id="view-auditoria" class="view"><div class="view-actions"><div class="filters"><input id="audit-search" placeholder="Ação ou entidade"><button class="btn btn-secondary" data-action="filter-audit">Filtrar</button></div></div><article class="panel"><div id="audit-table" class="table-wrap"></div></article></section>

    <section id="view-configuracoes" class="view"><article class="panel"><div class="panel-head"><div><p class="eyebrow">Parâmetros públicos</p><h2>Configurações do sistema</h2></div></div><div id="settings-list" class="settings-list"></div></article></section>
</main>

<dialog id="app-dialog"><form method="dialog" id="dialog-form"><div class="dialog-head"><div><p class="eyebrow" id="dialog-kicker"></p><h2 id="dialog-title"></h2></div><button class="icon-button" value="cancel" aria-label="Fechar">×</button></div><div id="dialog-body"></div><p id="dialog-error" class="form-error" hidden></p><div class="dialog-actions"><button class="btn btn-ghost" value="cancel">Cancelar</button><button class="btn btn-primary" id="dialog-submit" value="default">Salvar</button></div></form></dialog>
<div id="toast-region" class="toast-region" aria-live="polite"></div>
<script src="{{ asset('js/guarita.js') }}" defer></script>
</body>
</html>
