<?php
declare(strict_types=1);

/**
 * Router minimalista. Cada ruta es [METHOD, PATTERN, [Controller, action]].
 * El patrón usa marcadores tipo {id:\d+}.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/PassengerController.php';
require_once __DIR__ . '/controllers/DriverController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/ApiController.php';

function routes(): array
{
    return [
        ['GET',  '/',                    [HomeController::class,      'index']],

        ['GET',  '/login',               [AuthController::class,      'showLogin']],
        ['POST', '/login',               [AuthController::class,      'login']],
        ['GET',  '/register',            [AuthController::class,      'showRegister']],
        ['POST', '/register',            [AuthController::class,      'register']],
        ['POST', '/logout',              [AuthController::class,      'logout']],

        ['GET',  '/passenger',                  [PassengerController::class, 'dashboard']],
        ['GET',  '/passenger/request',          [PassengerController::class, 'showRequest']],
        ['POST', '/passenger/request',          [PassengerController::class, 'createRequest']],
        ['GET',  '/passenger/rides/{id:\d+}',   [PassengerController::class, 'showRide']],
        ['POST', '/passenger/rides/{id:\d+}/cancel', [PassengerController::class, 'cancel']],
        ['POST', '/passenger/rides/{id:\d+}/rate',   [PassengerController::class, 'rate']],
        ['GET',  '/passenger/history',          [PassengerController::class, 'history']],

        ['GET',  '/driver',                     [DriverController::class,    'dashboard']],
        ['POST', '/driver/online',              [DriverController::class,    'toggleOnline']],
        ['POST', '/driver/rides/{id:\d+}/accept',   [DriverController::class, 'accept']],
        ['POST', '/driver/rides/{id:\d+}/start',    [DriverController::class, 'start']],
        ['POST', '/driver/rides/{id:\d+}/complete', [DriverController::class, 'complete']],
        ['POST', '/driver/rides/{id:\d+}/rate',     [DriverController::class, 'rate']],
        ['GET',  '/driver/earnings',            [DriverController::class,    'earnings']],

        ['GET',  '/admin',                      [AdminController::class,     'dashboard']],
        ['GET',  '/admin/users',                [AdminController::class,     'users']],
        ['POST', '/admin/users/{id:\d+}/toggle',[AdminController::class,     'toggleUser']],
        ['GET',  '/admin/rides',                [AdminController::class,     'rides']],
        ['GET',  '/admin/fares',                [AdminController::class,     'fares']],
        ['POST', '/admin/fares',                [AdminController::class,     'updateFares']],

        // API JSON
        ['POST', '/api/fare-estimate',          [ApiController::class,       'estimate']],
        ['GET',  '/api/rides/{id:\d+}/status',  [ApiController::class,       'rideStatus']],
        ['GET',  '/api/driver/queue',           [ApiController::class,       'driverQueue']],
    ];
}

function dispatch(string $method, string $path): void
{
    $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?? '/', '/');
    if ($path === '/') {
        $path = '/';
    }

    foreach (routes() as [$m, $pattern, $handler]) {
        if ($m !== $method) {
            continue;
        }
        $regex = '#^' . preg_replace_callback(
            '/\{(\w+):([^}]+)\}/',
            fn($mm) => '(?P<' . $mm[1] . '>' . $mm[2] . ')',
            $pattern
        ) . '$#';
        if (preg_match($regex, $path, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->{$action}($params);
            return;
        }
    }

    http_response_code(404);
    echo view('errors/404');
}
