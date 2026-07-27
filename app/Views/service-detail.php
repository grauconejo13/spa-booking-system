<?php

declare(strict_types=1);

?>
<?php if ($detailError) : ?>
    <section class="page-header">
        <div class="container narrow">
            <p class="eyebrow">Service unavailable</p>
            <h1>Our service details are taking a short rest</h1>
            <p class="lede">We could not load this service right now. Please try again shortly.</p>
            <a class="button button-secondary" href="/services">Back to services</a>
        </div>
    </section>
<?php else : ?>
    <header class="page-header">
        <div class="container narrow">
            <p class="eyebrow">Treatment details</p>
            <h1><?= htmlspecialchars($service->name, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="lede"><?= htmlspecialchars($service->description, ENT_QUOTES, 'UTF-8') ?></p>
            <dl class="service-details service-detail-summary">
                <div>
                    <dt>Duration</dt>
                    <dd><?= $service->durationMinutes ?> minutes</dd>
                </div>
                <div>
                    <dt>Price</dt>
                    <dd>$<?= number_format($service->priceCents / 100, 2) ?></dd>
                </div>
            </dl>
            <div class="detail-actions">
                <a class="button button-secondary" href="/services">Back to services</a>
                <a class="button" href="/book/<?= $service->id ?>">Start booking</a>
            </div>
        </div>
    </header>

    <section class="section" aria-labelledby="therapists-heading">
        <div class="container">
            <p class="eyebrow">Qualified care</p>
            <h2 id="therapists-heading">Therapists for this service</h2>
            <?php if ($therapists === []) : ?>
                <div class="catalog-message" role="status">
                    <h3>Therapist availability is being refreshed</h3>
                    <p>No active therapists are currently listed for this service. Please check back soon.</p>
                </div>
            <?php else : ?>
                <div class="card-grid">
                <?php foreach ($therapists as $therapist) : ?>
                    <article class="therapist-card">
                        <h3><?= htmlspecialchars($therapist->name, ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if ($therapist->bio !== '') : ?>
                            <p><?= htmlspecialchars($therapist->bio, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
