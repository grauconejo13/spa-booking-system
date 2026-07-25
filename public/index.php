<?php

declare(strict_types=1);

use SpaBooking\Config\Environment;
use SpaBooking\Controllers\HomeController;
use SpaBooking\Controllers\ServicesController;
use SpaBooking\Database\PdoConnectionFactory;
use SpaBooking\Http\ErrorHandler;
use SpaBooking\Http\Response;
use SpaBooking\Http\Router;
use SpaBooking\Repositories\ServiceRepository;
use SpaBooking\Services\InMemoryServiceCatalog;
use SpaBooking\View\ViewRenderer;

$root = dirname(__DIR__);
$autoloadPath = $root . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Application dependencies are missing. Run composer install.';
    exit;
}

require $autoloadPath;

$views = new ViewRenderer($root . '/app/Views');
$errors = new ErrorHandler($views, false, $root . '/storage/logs/app.log');

try {
    Environment::load($root . '/.env');

    /** @var array{debug: bool, timezone: string} $appConfig */
    $appConfig = require $root . '/config/app.php';
    date_default_timezone_set($appConfig['timezone']);

    $errors = new ErrorHandler($views, $appConfig['debug'], $root . '/storage/logs/app.log');
    $errors->register();

    /** @var array{host: string, port: int, database: string, username: string,
     *     password: string, charset: string, options: array<int, mixed>} $databaseConfig */
    $databaseConfig = require $root . '/config/database.php';

    $home = new HomeController($views, new InMemoryServiceCatalog());
    $services = static function () use ($databaseConfig, $views): Response {
        $repository = new ServiceRepository((new PdoConnectionFactory($databaseConfig))->create());

        return (new ServicesController($views, $repository))->index();
    };
    $router = new Router(
        static fn (): Response => new Response(
            $views->render('errors/404', ['title' => 'Page not found']),
            404
        )
    );

    /** @var callable(Router, HomeController, callable(): Response): void $registerRoutes */
    $registerRoutes = require $root . '/routes/web.php';
    $registerRoutes($router, $home, $services);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $router->dispatch($method, $uri)->send();
} catch (Throwable $exception) {
    $errors->handle($exception);
}
