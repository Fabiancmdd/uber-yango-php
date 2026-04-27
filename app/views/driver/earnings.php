<?php /** @var array $stats */ /** @var array $byDay */ ?>
<h1>Mis ganancias</h1>

<div class="grid-3">
    <div class="card">
        <h3>Viajes completados</h3>
        <p class="big"><?= (int)$stats['total_completed'] ?></p>
    </div>
    <div class="card">
        <h3>Total ganado</h3>
        <p class="big">$<?= e(number_format((float)$stats['total_earnings'], 2)) ?></p>
    </div>
    <div class="card">
        <h3>Tarifa promedio</h3>
        <p class="big">$<?= e(number_format((float)$stats['avg_fare'], 2)) ?></p>
    </div>
</div>

<h2 class="mt">Por día</h2>
<?php if (!$byDay): ?>
    <p class="muted">Aún no completas viajes.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Día</th><th>Viajes</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($byDay as $d): ?>
            <tr>
                <td><?= e($d['day']) ?></td>
                <td><?= (int)$d['rides'] ?></td>
                <td>$<?= e(number_format((float)$d['total'], 2)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
