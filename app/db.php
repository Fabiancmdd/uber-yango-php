<?php
declare(strict_types=1);

/**
 * Singleton PDO con UTF8MB4, modo excepciones y fetch asociativo.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    /** @var array $config */
    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $db['host'], $db['port'], $db['name']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        if ($config['app_debug']) {
            throw $e;
        }
        http_response_code(500);
        die('No se pudo conectar a la base de datos.');
    }

    return $pdo;
}
