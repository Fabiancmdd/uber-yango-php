<?php
declare(strict_types=1);

/**
 * Carga simple de variables de entorno desde .env (formato KEY=VALUE).
 * No usamos vendor/ para mantener el proyecto sin dependencias.
 */
if (!function_exists('load_env')) {
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === '"' && $v[-1] === '"') || ($v[0] === "'" && $v[-1] === "'"))) {
            $v = substr($v, 1, -1);
        }
        if (!array_key_exists($k, $_ENV)) {
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default;
    }
    return $v;
}
} // function_exists guard

load_env(__DIR__ . '/../.env');

return [
    'app_name'     => env('APP_NAME', 'UberYango PHP'),
    'app_env'      => env('APP_ENV', 'production'),
    'app_debug'    => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'app_url'      => env('APP_URL', 'http://localhost:8000'),
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int)env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'uber_yango'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
    ],
    'currency'     => env('DEFAULT_CURRENCY', 'USD'),
];
