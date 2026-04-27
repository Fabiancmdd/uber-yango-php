<?php
declare(strict_types=1);

/**
 * Front controller. Carga config, ruteo y errores.
 */

// Si el servidor built-in de PHP recibe una petición a un archivo estático
// existente, dejamos que lo sirva directamente.
if (PHP_SAPI === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . ($url['path'] ?? '/');
    if (is_file($file) && $file !== __FILE__) {
        return false;
    }
}

$config = require __DIR__ . '/../app/config.php';

if ($config['app_debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

require __DIR__ . '/../app/router.php';

dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
