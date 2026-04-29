<div class="auth-box">
    <h1>Crear cuenta</h1>
    <form method="post" action="/register" id="register-form">
        <?= csrf_field() ?>
        <label>Nombre completo
            <input type="text" name="full_name" required value="<?= e(old('name')) ?>">
        </label>
        <label>Email
            <input type="email" name="email" required value="<?= e(old('email')) ?>">
        </label>
        <label>Teléfono
            <input type="text" name="phone" value="<?= e(old('phone')) ?>">
        </label>
        <fieldset class="role-picker">
            <legend>Quiero registrarme como:</legend>
            <label class="inline-radio">
                <input type="radio" name="role" value="passenger"
                       <?= old('role','passenger')==='passenger' ? 'checked' : '' ?>>
                Pasajero
            </label>
            <label class="inline-radio">
                <input type="radio" name="role" value="driver"
                       <?= old('role')==='driver' ? 'checked' : '' ?>>
                Conductor
            </label>
        </fieldset>
        <label class="driver-only" style="<?= old('role')==='driver' ? '' : 'display:none' ?>">Número de licencia
            <input type="text" name="license_number" value="<?= e(old('license')) ?>">
        </label>
        <label>Contraseña (mín. 6)
            <input type="password" name="password" required>
        </label>
        <label>Confirmar contraseña
            <input type="password" name="password_confirm" required>
        </label>
        <button class="btn btn-primary" type="submit">Crear cuenta</button>
    </form>
    <p class="muted">¿Ya tienes cuenta? <a href="/login">Inicia sesión</a>.</p>
</div>
<script>
document.querySelectorAll('input[name="role"]').forEach(r => {
    r.addEventListener('change', e => {
        document.querySelector('.driver-only').style.display =
            e.target.value === 'driver' ? '' : 'none';
    });
});
</script>
