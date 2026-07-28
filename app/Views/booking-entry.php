<?php

declare(strict_types=1);

use SpaBooking\Models\TherapistAvailabilityState;

$statusContent = static function (?TherapistAvailabilityState $state): array {
    return match ($state?->status) {
        TherapistAvailabilityState::AVAILABLE => ['Available', 'At least one appointment time is open.'],
        TherapistAvailabilityState::NOT_SCHEDULED => [
            'Not scheduled',
            'This therapist has no recurring working hours on this weekday.',
        ],
        TherapistAvailabilityState::FULLY_BOOKED => [
            'Fully booked',
            'This therapist is scheduled, but no appointment times remain.',
        ],
        TherapistAvailabilityState::UNAVAILABLE => [
            'Unavailable',
            'A date-specific closure or exception makes this therapist unavailable.',
        ],
        default => ['Choose a date to check availability', 'Availability will appear after you choose a date.'],
    };
};
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
                <div><dt>Duration</dt><dd><?= $service->durationMinutes ?> minutes</dd></div>
                <div><dt>Price</dt><dd>$<?= number_format($service->priceCents / 100, 2) ?></dd></div>
            </dl>
            <a class="service-link" href="/services/<?= $service->id ?>">Back to service details</a>
        </div>
    </header>

    <section id="booking-flow" class="section booking-flow" aria-labelledby="booking-flow-heading">
        <div class="container">
            <h2 id="booking-flow-heading" class="sr-only">Booking progress</h2>
            <nav class="booking-progress" aria-label="Booking progress">
                <ol>
                    <li class="<?= $hasTherapistSelection ? 'is-complete' : 'is-active' ?>"
                        <?= !$hasTherapistSelection ? 'aria-current="step"' : '' ?>>
                        <span>1</span> Therapist
                    </li>
                    <li class="<?= $hasTherapistSelection ? 'is-active' : 'is-disabled' ?>"
                        <?= $hasTherapistSelection ? 'aria-current="step"' : 'aria-disabled="true"' ?>>
                        <span>2</span> Date &amp; Time
                    </li>
                    <li class="is-disabled" aria-disabled="true"><span>3</span> Your Details</li>
                    <li class="is-disabled" aria-disabled="true"><span>4</span> Review</li>
                </ol>
            </nav>

            <section class="booking-step <?= $hasTherapistSelection ? 'is-complete' : 'is-active' ?>"
                aria-labelledby="therapist-choice-heading">
                <p class="eyebrow">Step 1</p>
                <h2 id="therapist-choice-heading">Choose therapist</h2>
                <?php if ($therapists === []) : ?>
                    <div class="catalog-message" role="status">
                        <h3>No qualified therapists</h3>
                        <p>No active therapists are currently assigned to this service.</p>
                    </div>
                <?php else : ?>
                    <form method="get" action="/book/<?= $service->id ?>#booking-flow">
                        <fieldset class="choice-list">
                            <legend class="sr-only">Therapist preference</legend>
                            <?php
                            $availableStates = array_filter(
                                $therapistStates,
                                static fn (TherapistAvailabilityState $state): bool =>
                                    $state->status === TherapistAvailabilityState::AVAILABLE
                            );
                            $noAvailableTherapists = $selectedDate !== ''
                                && $dateError === null
                                && $availableStates === [];
                            ?>
                            <label class="choice-card">
                                <input type="radio" name="therapist" value="any"
                                    <?= $hasTherapistSelection && $selectedTherapist === 'any' ? 'checked' : '' ?>>
                                <span>
                                    <strong>Any available therapist</strong>
                                    <small id="any-status" class="availability-status">
                                        <?= $noAvailableTherapists
                                            ? 'No therapists are available on this date.'
                                            : 'Choose any qualified therapist with an open time.' ?>
                                    </small>
                                </span>
                            </label>
                            <?php foreach ($therapists as $therapist) : ?>
                                <?php
                                $state = $therapistStates[$therapist->id] ?? null;
                                [$statusLabel, $statusExplanation] = $statusContent($state);
                                $disabled = $state !== null
                                    && $state->status !== TherapistAvailabilityState::AVAILABLE;
                                $statusId = 'therapist-status-' . $therapist->id;
                                ?>
                                <label class="choice-card <?= $disabled ? 'is-unavailable' : '' ?>">
                                    <input type="radio" name="therapist" value="<?= $therapist->id ?>"
                                        <?= $hasTherapistSelection
                                            && $selectedTherapist === (string) $therapist->id ? 'checked' : '' ?>
                                        <?= $disabled ? 'disabled aria-describedby="' . $statusId . '"' : '' ?>>
                                    <span>
                                        <strong><?= htmlspecialchars($therapist->name, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ($therapist->bio !== '') : ?>
                                            <small><?= htmlspecialchars($therapist->bio, ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                        <small id="<?= $statusId ?>" class="availability-status">
                                            <b><?= $statusLabel ?></b> — <?= $statusExplanation ?>
                                        </small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <?php if ($selectedDate !== '') : ?>
                            <input type="hidden" name="date"
                                value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <button class="button" type="submit">Continue to date and time</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="booking-step <?= $hasTherapistSelection ? 'is-active' : 'is-disabled' ?>"
                aria-labelledby="date-time-heading" <?= !$hasTherapistSelection ? 'aria-disabled="true"' : '' ?>>
                <p class="eyebrow">Step 2</p>
                <h2 id="date-time-heading">Choose date and time</h2>
                <?php if (!$hasTherapistSelection) : ?>
                    <p>Choose a therapist preference to continue.</p>
                <?php else : ?>
                    <form class="date-selection" method="get" action="/book/<?= $service->id ?>#booking-flow">
                        <input type="hidden" name="therapist"
                            value="<?= htmlspecialchars($selectedTherapist, ENT_QUOTES, 'UTF-8') ?>">
                        <label for="booking-date">Appointment date</label>
                        <input id="booking-date" name="date" type="date"
                            value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($dateError !== null) : ?>
                            <p class="field-error" role="alert">
                                <?= htmlspecialchars($dateError, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>
                        <button class="button" type="submit">View available times</button>
                    </form>

                    <?php if ($selectedDate === '' || $dateError !== null) : ?>
                        <p class="availability-message">Choose a valid date to preview available times.</p>
                    <?php elseif ($slots === []) : ?>
                        <?php $selectedState = $therapistStates[(int) $selectedTherapist] ?? null; ?>
                        <p class="availability-message" role="status">
                            <?php if ($selectedState?->status === TherapistAvailabilityState::NOT_SCHEDULED) : ?>
                                This therapist is not scheduled on this date.
                            <?php elseif ($selectedState?->status === TherapistAvailabilityState::UNAVAILABLE) : ?>
                                This therapist is unavailable because of a date-specific closure.
                            <?php elseif ($selectedState?->status === TherapistAvailabilityState::FULLY_BOOKED) : ?>
                                This therapist is fully booked on this date.
                            <?php else : ?>
                                No therapists are available on this date. Try another day.
                            <?php endif; ?>
                        </p>
                    <?php else : ?>
                        <form method="get" action="/book/<?= $service->id ?>#booking-flow">
                            <input type="hidden" name="therapist"
                                value="<?= htmlspecialchars($selectedTherapist, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="date"
                                value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                            <fieldset class="slot-list">
                                <legend>Available times</legend>
                                <?php foreach ($slots as $slot) : ?>
                                    <?php $slotValue = $slot->startsAt->format('H:i'); ?>
                                    <label class="time-slot">
                                        <input type="radio" name="time" value="<?= $slotValue ?>"
                                            <?= $selectedTime === $slotValue ? 'checked' : '' ?>>
                                        <span><?= $slot->startsAt->format('g:i A') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <?php if ($timeError !== null) : ?>
                                <p class="field-error" role="alert">
                                    <?= htmlspecialchars($timeError, ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                            <button class="button" type="submit">Select time</button>
                        </form>
                        <p class="slot-note">No therapist or appointment is reserved yet.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="booking-step is-disabled" aria-disabled="true" aria-labelledby="details-heading">
                <p class="eyebrow">Step 3 — Coming next</p>
                <h2 id="details-heading">Your details</h2>
                <p>Contact details will be collected in a later step.</p>
            </section>
            <section class="booking-step is-disabled" aria-disabled="true" aria-labelledby="review-heading">
                <p class="eyebrow">Step 4 — Coming next</p>
                <h2 id="review-heading">Review</h2>
                <p>Review and submission are not available yet.</p>
            </section>
        </div>
    </section>
<?php endif; ?>
