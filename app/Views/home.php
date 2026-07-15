<?php

declare(strict_types=1);
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <p class="eyebrow">One quiet place. Personal care.</p>
            <h1>Quiet care, thoughtfully planned</h1>
            <p class="lede">Discover restorative treatments from a fictional team of therapists at our single spa location.</p>
            <a class="button" href="/services">Explore services</a>
        </div>
        <div class="hero-note" aria-label="Booking feature status">
            <span>Coming in a later phase</span>
            <p>Online appointment requests with your preferred therapist&mdash;or any available therapist.</p>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="featured-heading">
    <div class="container">
        <p class="eyebrow">A gentle introduction</p>
        <h2 id="featured-heading">Featured services</h2>
        <div class="card-grid">
            <?php foreach ($featuredServices as $service): ?>
                <article class="service-card">
                    <h3><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="service-meta">
                        <?= (int) $service['durationMinutes'] ?> minutes
                        <span aria-hidden="true">&middot;</span>
                        $<?= number_format($service['priceCents'] / 100, 2) ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
