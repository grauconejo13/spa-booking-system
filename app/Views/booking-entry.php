<?php

declare(strict_types=1);

?>
<?php if ($bookingError) : ?>
    <section class="page-header">
        <div class="container narrow">
            <p class="eyebrow">Booking unavailable</p>
            <h1>Booking is taking a short rest</h1>
            <p class="lede">We could not prepare this booking right now. Please try again shortly.</p>
            <a class="button button-secondary" href="/services">Back to services</a>
        </div>
    </section>
<?php else : ?>
    <header class="page-header">
        <div class="container narrow">
            <p class="eyebrow">Start your appointment request</p>
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
            <a class="service-link" href="/services/<?= $service->id ?>">Back to service details</a>
        </div>
    </header>

    <section class="section" aria-labelledby="therapist-choice-heading">
        <div class="container booking-shell">
            <div>
                <p class="eyebrow">Step one</p>
                <h2 id="therapist-choice-heading">Choose a therapist preference</h2>
                <?php if ($therapists === []) : ?>
                    <div class="catalog-message" role="status">
                        <h3>No therapists are currently assigned</h3>
                        <p>Please check back soon while we refresh the team for this service.</p>
                    </div>
                <?php else : ?>
                    <fieldset class="choice-list">
                        <legend class="sr-only">Therapist preference</legend>
                        <label class="choice-card">
                            <input type="radio" name="therapist" value="any" checked>
                            <span>
                                <strong>Any available therapist</strong>
                                <small>We will match you with a qualified therapist.</small>
                            </span>
                        </label>
                        <?php foreach ($therapists as $therapist) : ?>
                            <label class="choice-card">
                                <input type="radio" name="therapist" value="<?= $therapist->id ?>">
                                <span>
                                    <strong><?= htmlspecialchars($therapist->name, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($therapist->bio !== '') : ?>
                                        <small><?= htmlspecialchars($therapist->bio, ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
            </div>

            <aside class="coming-soon-panel" aria-labelledby="date-time-heading">
                <p class="eyebrow">Next step</p>
                <h2 id="date-time-heading">Date and time</h2>
                <p>Date selection and available appointment times are coming in the next step.</p>
                <button class="button" type="button" disabled>Continue</button>
            </aside>
        </div>
    </section>
<?php endif; ?>
