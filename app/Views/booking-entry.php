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
                    <form id="availability-preview" method="get" action="/book/<?= $service->id ?>">
                    <fieldset class="choice-list">
                        <legend class="sr-only">Therapist preference</legend>
                        <label class="choice-card">
                            <input type="radio" name="therapist" value="any"
                                <?= $selectedTherapist === 'any' ? 'checked' : '' ?>>
                            <span>
                                <strong>Any available therapist</strong>
                                <small>We will match you with a qualified therapist.</small>
                            </span>
                        </label>
                        <?php foreach ($therapists as $therapist) : ?>
                            <label class="choice-card">
                                <input type="radio" name="therapist" value="<?= $therapist->id ?>"
                                    <?= $selectedTherapist === (string) $therapist->id ? 'checked' : '' ?>>
                                <span>
                                    <strong><?= htmlspecialchars($therapist->name, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($therapist->bio !== '') : ?>
                                        <small><?= htmlspecialchars($therapist->bio, ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <div class="date-selection">
                        <label for="booking-date">Appointment date</label>
                        <input id="booking-date" name="date" type="date"
                            value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($dateError !== null) : ?>
                            <p class="field-error" role="alert">
                                <?= htmlspecialchars($dateError, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>
                        <button class="button" type="submit">View available times</button>
                    </div>
                    </form>
                <?php endif; ?>
            </div>

            <aside class="coming-soon-panel" aria-labelledby="available-times-heading">
                <p class="eyebrow">Availability preview</p>
                <h2 id="available-times-heading">Available times</h2>
                <?php if ($therapists === []) : ?>
                    <p>Times will appear when qualified therapists are assigned.</p>
                <?php elseif ($selectedDate === '' || $dateError !== null) : ?>
                    <p>Choose a valid date to preview available appointment times.</p>
                <?php elseif ($slots === []) : ?>
                    <p>No appointment times are available on this date. Try another day.</p>
                <?php else : ?>
                    <div class="slot-list">
                    <?php foreach ($slots as $slot) : ?>
                        <span class="time-slot"
                            data-therapist-ids="<?= implode(',', $slot->therapistIds) ?>">
                            <?= $slot->startsAt->format('g:i A') ?>
                        </span>
                    <?php endforeach; ?>
                    </div>
                    <p class="slot-note">Times are a preview. No therapist or appointment is reserved yet.</p>
                <?php endif; ?>
                <button class="button" type="button" disabled>Continue</button>
            </aside>
        </div>
    </section>
<?php endif; ?>
