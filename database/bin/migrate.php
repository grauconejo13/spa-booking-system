<?php

declare(strict_types=1);

use SpaBooking\Config\Environment;
use SpaBooking\Database\MigrationRunner;
use SpaBooking\Database\PdoConnectionFactory;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    Environment::load($root . '/.env');
    /** @var array{host: string, port: int, database: string, username: string,
     *     password: string, charset: string, options: array<int, mixed>} $config */
    $config = require $root . '/config/database.php';
    $runner = new MigrationRunner(
        (new PdoConnectionFactory($config))->create(),
        $root . '/database/migrations'
    );
    $arguments = $_SERVER['argv'] ?? [];
    $rollback = is_array($arguments) && in_array('--rollback', $arguments, true);
    $completed = $rollback ? $runner->rollback() : $runner->migrate();
    $verb = $rollback ? 'Rolled back' : 'Applied';

    if ($completed === []) {
        fwrite(STDOUT, "Nothing to do.\n");
        exit(0);
    }

    foreach ($completed as $migration) {
        fwrite(STDOUT, sprintf("%s %s.\n", $verb, $migration));
    }
} catch (Throwable) {
    fwrite(STDERR, "Database migration failed. Check the database configuration and server logs.\n");
    exit(1);
}
