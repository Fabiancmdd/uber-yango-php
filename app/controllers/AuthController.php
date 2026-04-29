<?php
declare(strict_types=1);

class AuthController
{
    public function showLogin(array $params = []): void
    {
        if (current_user()) {
            redirect(url('/'));
        }
        render('auth/login', [], 'Iniciar sesión');
    }

    public function login(array $params = []): void
    {
        csrf_check();
        $email = strtolower(trim((string)input('email', '')));
        $password = (string)input('password', '');

        flash_old(['email' => $email]);

        if ($email === '' || $password === '') {
            flash('error', 'Completa email y contraseña.');
            redirect(url('/login'));
        }

        $stmt = db()->prepare('SELECT id, password_hash, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) {
            flash('error', 'Credenciales inválidas.');
            redirect(url('/login'));
        }
        if ($u['status'] !== 'active') {
            flash('error', 'Cuenta suspendida.');
            redirect(url('/login'));
        }

        login_user((int)$u['id']);
        clear_old();
        redirect(url('/'));
    }

    public function showRegister(array $params = []): void
    {
        if (current_user()) {
            redirect(url('/'));
        }
        render('auth/register', [], 'Crear cuenta');
    }

    public function register(array $params = []): void
    {
        csrf_check();
        $name     = trim((string)input('full_name', ''));
        $email    = strtolower(trim((string)input('email', '')));
        $phone    = trim((string)input('phone', ''));
        $password = (string)input('password', '');
        $confirm  = (string)input('password_confirm', '');
        $role     = (string)input('role', 'passenger');
        $license  = trim((string)input('license_number', ''));

        flash_old(compact('name', 'email', 'phone', 'role', 'license'));

        if (!in_array($role, ['passenger', 'driver'], true)) {
            flash('error', 'Rol inválido.');
            redirect(url('/register'));
        }
        if (mb_strlen($name) < 3) {
            flash('error', 'El nombre debe tener al menos 3 caracteres.');
            redirect(url('/register'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email inválido.');
            redirect(url('/register'));
        }
        if (strlen($password) < 6) {
            flash('error', 'La contraseña debe tener al menos 6 caracteres.');
            redirect(url('/register'));
        }
        if ($password !== $confirm) {
            flash('error', 'Las contraseñas no coinciden.');
            redirect(url('/register'));
        }
        if ($role === 'driver' && $license === '') {
            flash('error', 'Los conductores deben indicar su número de licencia.');
            redirect(url('/register'));
        }

        $exists = db()->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetchColumn()) {
            flash('error', 'Ya existe una cuenta con ese email.');
            redirect(url('/register'));
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare(
                'INSERT INTO users (full_name, email, phone, password_hash, role)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([$name, $email, $phone, $hash, $role]);
            $userId = (int)$pdo->lastInsertId();

            if ($role === 'passenger') {
                $pdo->prepare('INSERT INTO passengers (user_id) VALUES (?)')
                    ->execute([$userId]);
            } else {
                $pdo->prepare('INSERT INTO drivers (user_id, license_number) VALUES (?, ?)')
                    ->execute([$userId, $license]);
            }

            $pdo->commit();
            login_user($userId);
            clear_old();
            flash('success', '¡Cuenta creada con éxito!');
            redirect(url('/'));
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Error al crear la cuenta: ' . $e->getMessage());
            redirect(url('/register'));
        }
    }

    public function logout(array $params = []): void
    {
        csrf_check();
        logout_user();
        redirect(url('/login'));
    }
}
