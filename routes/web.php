<?php

declare(strict_types=1);

use SpaBooking\Controllers\HomeController;
use SpaBooking\Http\Router;

return static function (
    Router $router,
    HomeController $home,
    callable $services,
    callable $serviceDetail,
    callable $bookingEntry,
    callable $bookingReview,
    callable $bookingConfirm,
    callable $bookingConfirmation
): void {
    $router->get('/', [$home, 'index']);
    $router->get('/services', $services);
    $router->get('/services/{id}', $serviceDetail);
    $router->get('/book/{serviceId}', $bookingEntry);
    $router->post('/book/{serviceId}', $bookingReview);
    $router->post('/book/{serviceId}/confirm', $bookingConfirm);
    $router->get('/booking/confirmation/{reference}', $bookingConfirmation);
};
