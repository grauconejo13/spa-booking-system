<?php

declare(strict_types=1);

use SpaBooking\Models\TherapistAvailabilityState;

$steps = ['therapist' => 1, 'datetime' => 2, 'details' => 3, 'review' => 4];
$customerFields = [
    'name' => ['Full name', 'text', 120],
    'email' => ['Email address', 'email', 254],
    'phone' => ['Phone number', 'tel', 32],
];
$currentStep = $steps[$activeStep] ?? 1;
$escapedNotes = htmlspecialchars($customer['notes'] ?? '', ENT_QUOTES, 'UTF-8');
$therapistPreference = 'Any available therapist';
foreach ($therapists ?? [] as $therapist) {
    if (($selectedTherapist ?? 'any') === (string) $therapist->id) {
        $therapistPreference = $therapist->name;
        break;
    }
}
$statusContent = static fn (?TherapistAvailabilityState $state): array => match ($state?->status) {
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
$selectionQuery = static function (string $step) use (
    $selectedTherapist,
    $selectedDate,
    $selectedTime
): string {
    return http_build_query(array_filter([
        'step' => $step,
        'therapist' => $selectedTherapist,
        'date' => $selectedDate,
        'time' => $selectedTime,
    ], static fn (string $value): bool => $value !== ''));
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
                    <?php foreach (['Therapist', 'Date & Time', 'Your Details', 'Review'] as $index => $label) : ?>
                        <?php $number = $index + 1; ?>
                        <li class="<?= $number < $currentStep
                            ? 'is-complete'
                            : ($number === $currentStep ? 'is-active' : 'is-upcoming') ?>"
                            <?= $number === $currentStep ? 'aria-current="step"' : '' ?>>
                            <span><?= $number ?></span><b><?= $label ?></b>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <?php if ($activeStep === 'therapist') : ?>
                <section class="wizard-panel" aria-labelledby="therapist-heading">
                    <p class="eyebrow">Step 1</p>
                    <h2 id="therapist-heading" tabindex="-1" data-booking-focus>Choose therapist</h2>
                    <?php if ($therapists === []) : ?>
                        <div class="catalog-message" role="status">
                            <h3>No qualified therapists</h3>
                            <p>No active therapists are currently assigned to this service.</p>
                        </div>
                    <?php else : ?>
                        <form method="get" action="/book/<?= $service->id ?>#booking-flow">
                            <input type="hidden" name="step" value="datetime">
                            <?php if ($selectedDate !== '') : ?>
                                <input type="hidden" name="date"
                                    value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <?php if ($selectedTime !== '') : ?>
                                <input type="hidden" name="time"
                                    value="<?= htmlspecialchars($selectedTime, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <fieldset class="choice-list">
                                <legend class="sr-only">Therapist preference</legend>
                                <label class="choice-card">
                                    <input type="radio" name="therapist" value="any" required
                                        <?= $hasTherapistSelection && $selectedTherapist === 'any' ? 'checked' : '' ?>>
                                    <span><strong>Any available therapist</strong></span>
                                </label>
                                <?php foreach ($therapists as $therapist) : ?>
                                    <?php
                                    $state = $therapistStates[$therapist->id] ?? null;
                                    [$status, $explanation] = $statusContent($state);
                                    $disabled = $state !== null
                                        && $state->status !== TherapistAvailabilityState::AVAILABLE;
                                    $statusId = 'therapist-status-' . $therapist->id;
                                    ?>
                                    <label class="choice-card <?= $disabled ? 'is-unavailable' : '' ?>">
                                        <input type="radio" name="therapist" value="<?= $therapist->id ?>" required
                                            <?= $selectedTherapist === (string) $therapist->id ? 'checked' : '' ?>
                                            <?= $disabled
                                                ? 'disabled aria-describedby="' . $statusId . '"'
                                                : '' ?>>
                                        <span>
                                            <strong>
                                                <?= htmlspecialchars($therapist->name, ENT_QUOTES, 'UTF-8') ?>
                                            </strong>
                                            <?php if ($therapist->bio !== '') : ?>
                                                <small>
                                                    <?= htmlspecialchars($therapist->bio, ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            <?php endif; ?>
                                            <small id="<?= $statusId ?>" class="availability-status">
                                                <b><?= $status ?></b> — <?= $explanation ?>
                                            </small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <div class="wizard-navigation wizard-navigation-forward">
                                <button class="button" type="submit"
                                    aria-disabled="<?= $hasTherapistSelection ? 'false' : 'true' ?>">
                                    Next: Date &amp; Time
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
            <?php elseif ($activeStep === 'datetime') : ?>
                <section class="wizard-panel" aria-labelledby="datetime-heading">
                    <p class="eyebrow">Step 2</p>
                    <h2 id="datetime-heading" tabindex="-1" data-booking-focus>Choose date and time</h2>
                    <?php if ($bookingMessage !== null) : ?>
                        <div class="error-summary" role="alert" tabindex="-1" data-booking-focus>
                            <p><?= htmlspecialchars($bookingMessage, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endif; ?>
                    <p><strong>Therapist preference:</strong>
                        <?= htmlspecialchars($therapistPreference, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <form method="get" action="/book/<?= $service->id ?>#booking-flow">
                        <input type="hidden" name="step" value="details">
                        <input type="hidden" name="therapist"
                            value="<?= htmlspecialchars($selectedTherapist, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="date-selection">
                            <label for="booking-date">Appointment date</label>
                            <input id="booking-date" name="date" type="date" required
                                value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($dateError !== null) : ?>
                                <p class="field-error" role="alert">
                                    <?= htmlspecialchars($dateError, ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($slots !== []) : ?>
                            <fieldset class="slot-list">
                                <legend>Available times</legend>
                                <?php foreach ($slots as $slot) : ?>
                                    <?php $slotValue = $slot->startsAt->format('H:i'); ?>
                                    <label class="time-slot">
                                        <input type="radio" name="time" value="<?= $slotValue ?>" required
                                            <?= $selectedTime === $slotValue ? 'checked' : '' ?>>
                                        <span><?= $slot->startsAt->format('g:i A') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php elseif ($selectedDate !== '' && $dateError === null) : ?>
                            <p class="availability-message" role="status">
                                No appointment times are available on this date.
                            </p>
                        <?php endif; ?>
                        <?php if ($timeError !== null) : ?>
                            <p class="field-error" role="alert">
                                <?= htmlspecialchars($timeError, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>
                        <div class="wizard-navigation">
                            <a class="button button-secondary"
                                href="/book/<?= $service->id ?>?<?= $selectionQuery('therapist') ?>#booking-flow">
                                Back
                            </a>
                            <button class="button" type="submit"
                                aria-disabled="<?= $selectedSlot === null ? 'true' : 'false' ?>">
                                Next: Your Details
                            </button>
                        </div>
                    </form>
                </section>
            <?php elseif ($activeStep === 'details') : ?>
                <section class="wizard-panel" aria-labelledby="details-heading">
                    <p class="eyebrow">Step 3</p>
                    <h2 id="details-heading" tabindex="-1" data-booking-focus>Your details</h2>
                    <?php if ($formErrors !== []) : ?>
                        <div class="error-summary" role="alert" tabindex="-1" data-booking-focus>
                            <h3>Please correct the following</h3>
                            <ul>
                            <?php foreach ($formErrors as $field => $message) : ?>
                                <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form class="customer-details-form" method="post"
                        action="/book/<?= $service->id ?>#booking-flow" novalidate>
                        <input type="hidden" name="_token"
                            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="therapist"
                            value="<?= htmlspecialchars($selectedTherapist, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="date"
                            value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="time"
                            value="<?= htmlspecialchars($selectedTime, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($customerFields as $field => [$label, $type, $maxLength]) : ?>
                            <?php $error = $formErrors[$field] ?? null; ?>
                            <div class="form-field">
                                <label for="customer-<?= $field ?>"><?= $label ?></label>
                                <input id="customer-<?= $field ?>" name="<?= $field ?>" type="<?= $type ?>"
                                    value="<?= htmlspecialchars($customer[$field], ENT_QUOTES, 'UTF-8') ?>"
                                    maxlength="<?= $maxLength ?>" required
                                    <?= $error !== null
                                        ? 'aria-invalid="true" aria-describedby="customer-' . $field . '-error"'
                                        : '' ?>>
                                <?php if ($error !== null) : ?>
                                    <p id="customer-<?= $field ?>-error" class="field-error">
                                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-field">
                            <label for="customer-notes">Notes <span>(optional)</span></label>
                            <textarea id="customer-notes" name="notes" maxlength="1000"><?= $escapedNotes ?></textarea>
                        </div>
                        <div class="wizard-navigation">
                            <button class="button button-secondary" type="submit" name="step" value="datetime"
                                formnovalidate>Back</button>
                            <button class="button" type="submit" name="step" value="review">Review booking</button>
                        </div>
                    </form>
                </section>
            <?php else : ?>
                <section class="wizard-panel" aria-labelledby="review-heading">
                    <p class="eyebrow">Step 4</p>
                    <h2 id="review-heading" tabindex="-1" data-booking-focus>Review</h2>
                    <dl class="review-summary">
                        <div><dt>Service</dt><dd><?= htmlspecialchars($service->name, ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div>
                            <dt>Therapist</dt>
                            <dd><?= htmlspecialchars($therapistPreference, ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div><dt>Date</dt><dd><?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Time</dt><dd><?= $selectedSlot->startsAt->format('g:i A') ?></dd></div>
                        <div><dt>Duration</dt><dd><?= $service->durationMinutes ?> minutes</dd></div>
                        <div><dt>Price</dt><dd>$<?= number_format($service->priceCents / 100, 2) ?></dd></div>
                        <div><dt>Name</dt><dd><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Email</dt><dd>
                            <?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?>
                        </dd></div>
                        <div><dt>Phone</dt><dd>
                            <?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?>
                        </dd></div>
                        <?php if ($customer['notes'] !== '') : ?>
                            <div><dt>Notes</dt><dd><?= $escapedNotes ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <p><strong>Your appointment has not been booked yet.</strong></p>
                    <form method="post" action="/book/<?= $service->id ?>/confirm" data-confirm-booking>
                        <input type="hidden" name="_token"
                            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="therapist"
                            value="<?= htmlspecialchars($selectedTherapist, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="date"
                            value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="time"
                            value="<?= htmlspecialchars($selectedTime, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="submission_token"
                            value="<?= htmlspecialchars($submissionToken, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($customer as $field => $value) : ?>
                            <input type="hidden" name="<?= $field ?>"
                                value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                        <div class="wizard-navigation">
                            <button class="button button-secondary" type="submit" name="step" value="details"
                                formaction="/book/<?= $service->id ?>#booking-flow">
                                Back
                            </button>
                            <button class="button" type="submit" data-confirm-button>Confirm booking</button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
