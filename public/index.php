<?php

declare(strict_types=1);

$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($autoloadPath)) {
    require $autoloadPath;
}

http_response_code(200);
header('Content-Type: text/html; charset=UTF-8');

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spa Booking System</title>
</head>
<body>
    <main>
        <h1>Spa Booking System</h1>
        <p>Project foundation ready. Booking features are planned but not implemented.</p>
    </main>
</body>
</html>
