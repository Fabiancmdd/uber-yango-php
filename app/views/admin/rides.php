<?php /** @var array $rides */ ?>
<h1>Viajes</h1>
<?php if (!$rides): ?>
    <p class="muted">No hay viajes aún.</p>
<?php else: ?>
    <table class="table">
        <thead><tr>
            <th>#</th><th>Pasajero</th><th>Conductor</th>
            <th>Origen → Destino</th><th>Estado</th><th>km</th><th>Tarifa</th><th>Fecha</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rides as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['passenger_name']) ?></td>
                <td><?= e($r['driver_name'] ?? '—') ?></td>
                <td><?= e($r['origin_address']) ?> → <?= e($r['destination_address']) ?></td>
                <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td><?= e(number_format((float)$r['distance_km'], 2)) ?></td>
                <td>$<?= e(number_format((float)($r['fare_final'] ?? $r['fare_estimated']), 2)) ?></td>
                <td><?= e($r['requested_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
