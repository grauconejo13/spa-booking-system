<?php

declare(strict_types=1);

?>
<header class="page-header">
    <div class="container narrow">
        <p class="eyebrow">Fictional treatments, transparent details</p>
        <h1>Spa services</h1>
        <p class="lede">Browse our active treatment catalogue. Online booking
and therapist selection arrive in a later phase.</p>
    </div>
</header>

<section class="section" aria-label="Available services">
    <div class="container">
        <?php if ($catalogError) : ?>
            <div class="catalog-message" role="status">
                <h2>Services are taking a short rest</h2>
                <p>We could not load the catalogue right now. Please try again shortly.</p>
            </div>
        <?php elseif ($services === []) : ?>
            <div class="catalog-message" role="status">
                <h2>New treatments are on the way</h2>
                <p>There are no services available to browse right now. Please check back soon.</p>
            </div>
        <?php else : ?>
            <div class="card-grid">
            <?php foreach ($services as $service) : ?>
                <article class="service-card">
                    <h2><?= htmlspecialchars($service->name, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($service->description, ENT_QUOTES, 'UTF-8') ?></p>
                    <dl class="service-details">
                        <div>
                            <dt>Duration</dt>
                            <dd><?= $service->durationMinutes ?> minutes</dd>
                        </div>
                        <div>
                            <dt>Price</dt>
                            <dd>$<?= number_format($service->priceCents / 100, 2) ?></dd>
                        </div>
                    </dl>
                    <a class="service-link" href="/services/<?= $service->id ?>">
                        View service details
                    </a>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
