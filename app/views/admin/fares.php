<?php /** @var array $fares */ ?>
<h1>Tarifas</h1>
<p class="muted">Tarifa = base + (per_km × km) + (per_min × min), con un mínimo configurable.</p>
<form method="post" action="/admin/fares">
    <?= csrf_field() ?>
    <table class="table">
        <thead><tr>
            <th>Categoría</th><th>Base</th><th>Por km</th><th>Por min</th><th>Mínimo</th><th>Moneda</th>
        </tr></thead>
        <tbody>
        <?php foreach ($fares as $f): ?>
            <tr>
                <td><strong><?= e($f['category']) ?></strong></td>
                <td><input type="number" step="0.01" min="0"
                       name="fare[<?= e($f['category']) ?>][base_fare]"
                       value="<?= e($f['base_fare']) ?>" required></td>
                <td><input type="number" step="0.01" min="0"
                       name="fare[<?= e($f['category']) ?>][per_km]"
                       value="<?= e($f['per_km']) ?>" required></td>
                <td><input type="number" step="0.01" min="0"
                       name="fare[<?= e($f['category']) ?>][per_min]"
                       value="<?= e($f['per_min']) ?>" required></td>
                <td><input type="number" step="0.01" min="0"
                       name="fare[<?= e($f['category']) ?>][min_fare]"
                       value="<?= e($f['min_fare']) ?>" required></td>
                <td><input type="text" maxlength="8"
                       name="fare[<?= e($f['category']) ?>][currency]"
                       value="<?= e($f['currency']) ?>" required style="width:80px"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button class="btn btn-primary" type="submit">Guardar tarifas</button>
</form>
