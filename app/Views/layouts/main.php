<?php

declare(strict_types=1);

$pageTitle = isset($title) ? (string) $title . ' | Willow & Still Spa' : 'Willow & Still Spa';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A fictional single-location spa booking portfolio project.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header class="site-header">
        <div class="container nav-shell">
            <a class="brand" href="/" aria-label="Willow and Still Spa home">
                <span aria-hidden="true">W&amp;S</span>
                <strong>Willow &amp; Still Spa</strong>
            </a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
                <span class="sr-only">Toggle navigation</span>
                <span aria-hidden="true">Menu</span>
            </button>
            <nav id="primary-navigation" class="primary-nav" aria-label="Primary navigation">
                <a href="/">Home</a>
                <a href="/services">Services</a>
            </nav>
        </div>
    </header>
    <main id="main-content">
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <strong>Willow &amp; Still Spa</strong>
                <p>A fictional retreat created for a portfolio demonstration.</p>
            </div>
            <div>
                <strong>Visit</strong>
                <p>18 Willow Lane<br>North Harbor, IL 60000</p>
            </div>
            <div>
                <strong>Hours</strong>
                <p>Tuesday&ndash;Saturday<br>9:00 AM&ndash;6:00 PM</p>
            </div>
        </div>
    </footer>
</body>
</html>
