<?php
/** @var array $user */ /** @var array $driver */ /** @var ?array $vehicle */
/** @var ?array $activeRide */ /** @var array $queue */ /** @var array $recent */
?>
<h1>Hola, <?= e(explode(' ', $user['full_name'])[0]) ?> 🚗</h1>

<div class="card">
    <h3>Estado: <?= ((int)$driver['is_online']) ? '🟢 En línea' : '⚪ Desconectado' ?></h3>
    <p>⭐ <?= e(number_format((float)$driver['rating_avg'], 2)) ?>
       · Viajes: <?= (int)$driver['total_rides'] ?>
       · Ganancias totales: $<?= e(number_format((float)$driver['total_earnings'], 2)) ?></p>
    <?php if ($vehicle): ?>
        <p>Vehículo: <strong><?= e($vehicle['brand'] . ' ' . $vehicle['model']) ?></strong>
           (<?= e($vehicle['color']) ?>) · placa <?= e($vehicle['plate']) ?>
           · categoría <?= e($vehicle['category']) ?></p>
    <?php else: ?>
        <p class="muted">⚠ No tienes vehículo registrado. Pide al admin que registre uno para aceptar viajes.</p>
    <?php endif; ?>
    <form method="post" action="/driver/online" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="online" value="<?= ((int)$driver['is_online']) ? 0 : 1 ?>">
        <button class="btn <?= ((int)$driver['is_online']) ? 'btn-ghost' : 'btn-primary' ?>" type="submit">
            <?= ((int)$driver['is_online']) ? 'Desconectarme' : 'Conectarme' ?>
        </button>
    </form>
</div>

<?php if ($activeRide): ?>
    <div class="card prominent mt">
        <h2>Viaje en curso · #<?= (int)$activeRide['id'] ?></h2>
        <p><strong>Pasajero:</strong> <?= e($activeRide['passenger_name']) ?>
           · ⭐ <?= e(number_format((float)$activeRide['passenger_rating'], 2)) ?></p>
        <p><strong>Origen:</strong> <?= e($activeRide['origin_address']) ?></p>
        <p><strong>Destino:</strong> <?= e($activeRide['destination_address']) ?></p>
        <p><strong>Tarifa estimada:</strong> $<?= e(number_format((float)$activeRide['fare_estimated'], 2)) ?>
           · <?= e(number_format((float)$activeRide['distance_km'], 2)) ?> km</p>

        <?php if ($activeRide['status'] === 'accepted'): ?>
            <form method="post" action="/driver/rides/<?= (int)$activeRide['id'] ?>/start" class="inline">
                <?= csrf_field() ?>
                <button class="btn btn-primary">Iniciar viaje</button>
            </form>
        <?php elseif ($activeRide['status'] === 'in_progress'): ?>
            <form method="post" action="/driver/rides/<?= (int)$activeRide['id'] ?>/complete" class="inline">
                <?= csrf_field() ?>
                <button class="btn btn-primary">Finalizar viaje</button>
            </form>
        <?php endif; ?>
    </div>
<?php elseif ((int)$driver['is_online'] === 1): ?>
    <h2 class="mt">Solicitudes disponibles</h2>
    <?php if (!$queue): ?>
        <p class="muted">No hay solicitudes en este momento. Mantente atento.</p>
    <?php else: ?>
        <div class="grid-2">
            <?php foreach ($queue as $r): ?>
                <div class="card">
                    <h3>#<?= (int)$r['id'] ?> · <?= e($r['passenger_name']) ?>
                       <small>⭐ <?= e(number_format((float)$r['passenger_rating'], 2)) ?></small></h3>
                    <p><strong>Origen:</strong> <?= e($r['origin_address']) ?></p>
                    <p><strong>Destino:</strong> <?= e($r['destination_address']) ?></p>
                    <p><?= e(number_format((float)$r['distance_km'], 2)) ?> km
                       · 💰 $<?= e(number_format((float)$r['fare_estimated'], 2)) ?></p>
                    <form method="post" action="/driver/rides/<?= (int)$r['id'] ?>/accept">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary" type="submit"
                                <?= !$vehicle ? 'disabled' : '' ?>>Aceptar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<h2 class="mt">Últimos viajes</h2>
<?php if (!$recent): ?>
    <p class="muted">Sin viajes aún.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>#</th><th>Origen → Destino</th><th>Estado</th><th>Tarifa</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['origin_address']) ?> → <?= e($r['destination_address']) ?></td>
                <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td>$<?= e(number_format((float)($r['fare_final'] ?? 0), 2)) ?></td>
                <td><?= e($r['requested_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
