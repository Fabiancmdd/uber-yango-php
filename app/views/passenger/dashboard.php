<?php /** @var array $user */ /** @var ?array $activeRide */ /** @var array $recent */ ?>
<h1>Hola, <?= e(explode(' ', $user['full_name'])[0]) ?> 👋</h1>

<?php if ($activeRide): ?>
    <div class="card prominent">
        <h2>Viaje activo · #<?= (int)$activeRide['id'] ?></h2>
        <p><strong>Estado:</strong> <span class="status status-<?= e($activeRide['status']) ?>"><?= e($activeRide['status']) ?></span></p>
        <p><strong>Origen:</strong> <?= e($activeRide['origin_address']) ?></p>
        <p><strong>Destino:</strong> <?= e($activeRide['destination_address']) ?></p>
        <p><strong>Tarifa estimada:</strong> $<?= e(number_format((float)$activeRide['fare_estimated'], 2)) ?></p>
        <?php if (!empty($activeRide['driver_name'])): ?>
            <p><strong>Conductor:</strong> <?= e($activeRide['driver_name']) ?>
               · <?= e($activeRide['brand'] . ' ' . $activeRide['model']) ?>
               (<?= e($activeRide['color']) ?>) · placa <?= e($activeRide['plate']) ?></p>
        <?php endif; ?>
        <a class="btn btn-primary" href="/passenger/rides/<?= (int)$activeRide['id'] ?>">Ver detalle</a>
    </div>
<?php else: ?>
    <div class="card">
        <h2>¿Te llevamos a algún lado?</h2>
        <p>Solicita un viaje en segundos seleccionando origen y destino en el mapa.</p>
        <a class="btn btn-primary" href="/passenger/request">Solicitar viaje</a>
    </div>
<?php endif; ?>

<h2 class="mt">Viajes recientes</h2>
<?php if (!$recent): ?>
    <p class="muted">Aún no tienes viajes.</p>
<?php else: ?>
    <table class="table">
        <thead><tr>
            <th>#</th><th>Origen → Destino</th><th>Estado</th><th>Tarifa</th><th>Fecha</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['origin_address']) ?> → <?= e($r['destination_address']) ?></td>
                <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td>$<?= e(number_format((float)($r['fare_final'] ?? $r['fare_estimated']), 2)) ?></td>
                <td><?= e($r['requested_at']) ?></td>
                <td><a class="link" href="/passenger/rides/<?= (int)$r['id'] ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
