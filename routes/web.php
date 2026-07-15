<?php

declare(strict_types=1);

use SpaBooking\Controllers\HomeController;
use SpaBooking\Http\Router;

return static function (Router $router, HomeController $home): void {
    $router->get('/', [$home, 'index']);
    $router->get('/services', [$home, 'services']);
};
