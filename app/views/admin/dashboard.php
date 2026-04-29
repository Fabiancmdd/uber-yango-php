<?php /** @var array $stats */ ?>
<h1>Resumen general</h1>
<div class="grid-3">
    <div class="card"><h3>Usuarios</h3><p class="big"><?= (int)$stats['users'] ?></p>
        <small>Pasajeros: <?= (int)$stats['passengers'] ?> · Conductores: <?= (int)$stats['drivers'] ?></small></div>
    <div class="card"><h3>Viajes</h3><p class="big"><?= (int)$stats['rides'] ?></p>
        <small>Activos: <?= (int)$stats['active'] ?> · Completados: <?= (int)$stats['completed'] ?></small></div>
    <div class="card"><h3>Ingresos</h3><p class="big">$<?= e(number_format((float)$stats['revenue'], 2)) ?></p>
        <small>Solo viajes completados</small></div>
</div>

<div class="mt">
    <a class="btn btn-primary" href="/admin/users">Gestionar usuarios</a>
    <a class="btn btn-ghost"   href="/admin/rides">Ver viajes</a>
    <a class="btn btn-ghost"   href="/admin/fares">Configurar tarifas</a>
</div>
