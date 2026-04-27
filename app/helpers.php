<?php
declare(strict_types=1);

/**
 * Funciones utilitarias compartidas.
 */

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    start_session();
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['_csrf'] ?? '', $sent)) {
        http_response_code(419);
        die('CSRF token inválido.');
    }
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    start_session();
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['_flash'][$key] ?? null;
    if ($v !== null) {
        unset($_SESSION['_flash'][$key]);
    }
    return $v;
}

function old(string $key, string $default = ''): string
{
    start_session();
    $v = $_SESSION['_old'][$key] ?? $default;
    return (string)$v;
}

function flash_old(array $data): void
{
    start_session();
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    start_session();
    unset($_SESSION['_old']);
}

function view(string $path, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $path . '.php';
    return (string)ob_get_clean();
}

function render(string $path, array $data = [], string $title = ''): void
{
    $content = view($path, $data);
    require __DIR__ . '/views/layout.php';
    clear_old();
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function input(string $key, ?string $default = null): ?string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function url(string $path = ''): string
{
    /** @var array $config */
    $config = require __DIR__ . '/config.php';
    return rtrim((string)$config['app_url'], '/') . '/' . ltrim($path, '/');
}

/**
 * Distancia Haversine en kilómetros entre dos coordenadas geográficas.
 */
function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * Estima la duración del viaje en minutos (asume velocidad promedio urbana).
 */
function estimate_duration_min(float $km, float $kmh = 30.0): float
{
    if ($km <= 0) {
        return 0.0;
    }
    return round(($km / $kmh) * 60, 2);
}

/**
 * Calcula la tarifa para una categoría dada.
 */
function calculate_fare(string $category, float $km, float $minutes): array
{
    $stmt = db()->prepare('SELECT * FROM fare_settings WHERE category = ? LIMIT 1');
    $stmt->execute([$category]);
    $row = $stmt->fetch();
    if (!$row) {
        $row = ['base_fare' => 2.00, 'per_km' => 0.80, 'per_min' => 0.20, 'min_fare' => 3.00, 'currency' => 'USD'];
    }
    $fare = (float)$row['base_fare']
          + (float)$row['per_km'] * $km
          + (float)$row['per_min'] * $minutes;
    $fare = max($fare, (float)$row['min_fare']);
    return [
        'fare'     => round($fare, 2),
        'currency' => $row['currency'],
        'breakdown' => [
            'base'    => (float)$row['base_fare'],
            'per_km'  => (float)$row['per_km'],
            'per_min' => (float)$row['per_min'],
            'km'      => round($km, 3),
            'minutes' => round($minutes, 2),
        ],
    ];
}
