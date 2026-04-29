<?php /** @var string $content */ /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') ? $title . ' · UberYango PHP' : 'UberYango PHP') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<header class="topbar">
    <a href="/" class="logo">🚖 UberYango <span>PHP</span></a>
    <nav>
        <?php $u = current_user(); ?>
        <?php if ($u): ?>
            <?php if ($u['role'] === 'passenger'): ?>
                <a href="/passenger">Inicio</a>
                <a href="/passenger/request">Solicitar viaje</a>
                <a href="/passenger/history">Historial</a>
            <?php elseif ($u['role'] === 'driver'): ?>
                <a href="/driver">Panel</a>
                <a href="/driver/earnings">Ganancias</a>
            <?php elseif ($u['role'] === 'admin'): ?>
                <a href="/admin">Resumen</a>
                <a href="/admin/users">Usuarios</a>
                <a href="/admin/rides">Viajes</a>
                <a href="/admin/fares">Tarifas</a>
            <?php endif; ?>
            <span class="user">👤 <?= e($u['full_name']) ?> · <em><?= e($u['role']) ?></em></span>
            <form method="post" action="/logout" class="inline">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit">Salir</button>
            </form>
        <?php else: ?>
            <a href="/login">Ingresar</a>
            <a href="/register" class="btn btn-primary btn-sm">Crear cuenta</a>
        <?php endif; ?>
    </nav>
</header>

<main class="container">
    <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error"><?= e($msg) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>

<footer class="footer">
    <small>UberYango PHP · MVP educativo · <?= date('Y') ?></small>
</footer>

<script src="/assets/js/app.js"></script>
</body>
</html>
