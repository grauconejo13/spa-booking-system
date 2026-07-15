<?php

declare(strict_types=1);
?>
<header class="page-header">
    <div class="container narrow">
        <p class="eyebrow">Fictional treatments, transparent details</p>
        <h1>Spa services</h1>
        <p class="lede">Browse our temporary demonstration catalogue. Online booking and therapist selection arrive in a later phase.</p>
    </div>
</header>

<section class="section" aria-label="Available services">
    <div class="container card-grid">
        <?php foreach ($services as $service): ?>
            <article class="service-card">
                <h2><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <dl class="service-details">
                    <div>
                        <dt>Duration</dt>
                        <dd><?= (int) $service['durationMinutes'] ?> minutes</dd>
                    </div>
                    <div>
                        <dt>Price</dt>
                        <dd>$<?= number_format($service['priceCents'] / 100, 2) ?></dd>
                    </div>
                </dl>
            </article>
        <?php endforeach; ?>
    </div>
</section>

