<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function current_user(): ?array
{
    start_session();
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }
    static $cache = [];
    if (isset($cache[$id])) {
        return $cache[$id];
    }
    $stmt = db()->prepare('SELECT id, full_name, email, phone, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $cache[$id] = $u;
}

function login_user(int $userId): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('error', 'Debes iniciar sesión para continuar.');
        redirect(url('/login'));
    }
    if ($u['status'] === 'suspended') {
        logout_user();
        flash('error', 'Tu cuenta está suspendida. Contacta al administrador.');
        redirect(url('/login'));
    }
    return $u;
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('No tienes permiso para acceder a esta sección.');
    }
    return $u;
}

function passenger_id_for_user(int $userId): ?int
{
    $stmt = db()->prepare('SELECT id FROM passengers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function driver_id_for_user(int $userId): ?int
{
    $stmt = db()->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}
