<?php

declare(strict_types=1);

use SpaBooking\Config\Environment;

return [
    'environment' => Environment::get('APP_ENV', 'production') ?? 'production',
    'debug' => Environment::bool('APP_DEBUG', false),
    'url' => Environment::get('APP_URL', 'http://localhost:8000') ?? 'http://localhost:8000',
    'timezone' => Environment::get('APP_TIMEZONE', 'America/Chicago') ?? 'America/Chicago',
];
