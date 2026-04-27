<?php /** @var array $ride */ /** @var ?array $myRating */ ?>
<h1>Viaje #<?= (int)$ride['id'] ?></h1>
<p>Estado: <span class="status status-<?= e($ride['status']) ?>" id="ride-status"><?= e($ride['status']) ?></span></p>

<div class="grid-2">
    <div class="card">
        <h3>Recorrido</h3>
        <p><strong>Origen:</strong> <?= e($ride['origin_address']) ?></p>
        <p><strong>Destino:</strong> <?= e($ride['destination_address']) ?></p>
        <p><strong>Distancia:</strong> <?= e(number_format((float)$ride['distance_km'], 2)) ?> km</p>
        <p><strong>Duración est.:</strong> <?= e(number_format((float)$ride['duration_min'], 0)) ?> min</p>
        <p><strong>Categoría:</strong> <?= e($ride['category']) ?> · Pago: <?= e($ride['payment_method']) ?></p>
        <p><strong>Tarifa estimada:</strong> $<?= e(number_format((float)$ride['fare_estimated'], 2)) ?></p>
        <?php if ($ride['fare_final']): ?>
            <p><strong>Tarifa final:</strong> $<?= e(number_format((float)$ride['fare_final'], 2)) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Conductor</h3>
        <?php if (!empty($ride['driver_name'])): ?>
            <p><?= e($ride['driver_name']) ?> · ⭐ <?= e(number_format((float)$ride['driver_rating'], 2)) ?></p>
            <p><?= e($ride['brand'] . ' ' . $ride['model']) ?> (<?= e($ride['color']) ?>)</p>
            <p>Placa: <strong><?= e($ride['plate']) ?></strong></p>
            <?php if (!empty($ride['driver_phone'])): ?>
                <p>📞 <?= e($ride['driver_phone']) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="muted">Buscando conductor cercano...</p>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($ride['status'], ['requested','accepted'], true)): ?>
    <form method="post" action="/passenger/rides/<?= (int)$ride['id'] ?>/cancel" class="mt"
          onsubmit="return confirm('¿Cancelar viaje?')">
        <?= csrf_field() ?>
        <input type="text" name="reason" placeholder="Motivo (opcional)">
        <button class="btn btn-ghost" type="submit">Cancelar viaje</button>
    </form>
<?php endif; ?>

<?php if ($ride['status'] === 'completed' && !$myRating): ?>
    <div class="card mt">
        <h3>Califica tu viaje</h3>
        <form method="post" action="/passenger/rides/<?= (int)$ride['id'] ?>/rate">
            <?= csrf_field() ?>
            <label>Estrellas
                <select name="stars" required>
                    <option value="5">★★★★★</option>
                    <option value="4">★★★★</option>
                    <option value="3">★★★</option>
                    <option value="2">★★</option>
                    <option value="1">★</option>
                </select>
            </label>
            <label>Comentario
                <textarea name="comment" rows="2" maxlength="500"></textarea>
            </label>
            <button class="btn btn-primary" type="submit">Enviar</button>
        </form>
    </div>
<?php elseif ($myRating): ?>
    <p class="mt">⭐ Calificaste con <?= (int)$myRating['stars'] ?>/5.
       <?php if ($myRating['comment']): ?> «<?= e($myRating['comment']) ?>»<?php endif; ?></p>
<?php endif; ?>

<script>
// Polling de estado mientras el viaje no esté en estado final
(function(){
    const id = <?= (int)$ride['id'] ?>;
    const finalStates = ['completed','cancelled'];
    let last = '<?= e($ride['status']) ?>';
    if (finalStates.includes(last)) return;
    setInterval(async () => {
        try {
            const r = await fetch(`/api/rides/${id}/status`);
            if (!r.ok) return;
            const data = await r.json();
            if (data.status !== last) {
                location.reload();
            }
        } catch(_) {}
    }, 4000);
})();
</script>
