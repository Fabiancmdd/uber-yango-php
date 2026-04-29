<?php /** @var array $rides */ ?>
<h1>Historial de viajes</h1>
<?php if (!$rides): ?>
    <p class="muted">No tienes viajes aún. <a href="/passenger/request">Solicita uno</a>.</p>
<?php else: ?>
    <table class="table">
        <thead><tr>
            <th>#</th><th>Origen → Destino</th><th>Conductor</th>
            <th>Estado</th><th>Tarifa</th><th>Fecha</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rides as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['origin_address']) ?> → <?= e($r['destination_address']) ?></td>
                <td><?= e($r['driver_name'] ?? '—') ?></td>
                <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td>$<?= e(number_format((float)($r['fare_final'] ?? $r['fare_estimated']), 2)) ?></td>
                <td><?= e($r['requested_at']) ?></td>
                <td><a class="link" href="/passenger/rides/<?= (int)$r['id'] ?>">Detalle</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
