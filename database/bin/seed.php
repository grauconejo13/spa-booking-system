<?php

declare(strict_types=1);

use SpaBooking\Config\Environment;
use SpaBooking\Database\DatabaseSeeder;
use SpaBooking\Database\PdoConnectionFactory;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    Environment::load($root . '/.env');
    /** @var array{host: string, port: int, database: string, username: string,
     *     password: string, charset: string, options: array<int, mixed>} $config */
    $config = require $root . '/config/database.php';
    (new DatabaseSeeder((new PdoConnectionFactory($config))->create()))->seed();
    fwrite(STDOUT, "Fictional demo data seeded.\n");
} catch (Throwable) {
    fwrite(STDERR, "Database seeding failed. Check that migrations are current and review the server logs.\n");
    exit(1);
}
