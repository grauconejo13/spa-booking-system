<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicRoot = realpath(__DIR__);
$publicFile = is_string($path) ? realpath(__DIR__ . $path) : false;

if (
    $path !== '/'
    && is_string($publicRoot)
    && is_string($publicFile)
    && str_starts_with($publicFile, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($publicFile)
) {
    return false;
}

require __DIR__ . '/index.php';
