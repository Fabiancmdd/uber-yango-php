<div class="auth-box">
    <h1>Iniciar sesión</h1>
    <form method="post" action="/login">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" required value="<?= e(old('email')) ?>" autofocus>
        </label>
        <label>Contraseña
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Entrar</button>
    </form>
    <p class="muted">¿No tienes cuenta? <a href="/register">Crea una</a>.</p>
</div>
