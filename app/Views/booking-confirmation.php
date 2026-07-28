<?php

declare(strict_types=1);

?>
<header class="page-header">
    <div class="container narrow">
        <p class="eyebrow">Booking request received</p>
        <h1>Your appointment request has been received.</h1>
        <p class="lede">Keep your booking reference for future correspondence.</p>
    </div>
</header>
<section class="section" aria-labelledby="confirmation-summary-heading">
    <div class="container narrow">
        <h2 id="confirmation-summary-heading">Request summary</h2>
        <dl class="review-summary">
            <div>
                <dt>Reference</dt>
                <dd><?= htmlspecialchars($appointment->reference, ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><?= htmlspecialchars(ucfirst($appointment->status), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div><dt>Service</dt><dd><?= htmlspecialchars($appointment->serviceName, ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div>
                <dt>Therapist</dt>
                <dd><?= htmlspecialchars($appointment->therapistName ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div><dt>Date</dt><dd><?= $localStart->format('F j, Y') ?></dd></div>
            <div><dt>Time</dt><dd><?= $localStart->format('g:i A') ?></dd></div>
            <div><dt>Duration</dt><dd><?= $appointment->durationMinutes ?> minutes</dd></div>
            <div><dt>Price</dt><dd>$<?= number_format($appointment->priceCents / 100, 2) ?></dd></div>
            <div>
                <dt>Customer</dt>
                <dd><?= htmlspecialchars($appointment->customerName, ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
        </dl>
        <p>This request is pending and has not yet been confirmed by the spa.</p>
        <a class="button button-secondary" href="/services">Browse services</a>
    </div>
</section>
