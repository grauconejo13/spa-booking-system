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
$currentStep = !$hasTherapistSelection ? 1 : ($selectedSlot === null ? 2 : ($reviewReady ? 4 : 3));
$therapistPreference = 'Any available therapist';
foreach ($therapists as $therapist) {
    if ($selectedTherapist === (string) $therapist->id) {
        $therapistPreference = $therapist->name;
        break;
    }
}
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
                    <li class="<?= $currentStep > 1 ? 'is-complete' : 'is-active' ?>"
                        <?= $currentStep === 1 ? 'aria-current="step"' : '' ?>>
                        <span>1</span> Therapist
                    </li>
                    <li class="<?= $currentStep > 2 ? 'is-complete' : ($currentStep === 2 ? 'is-active' : 'is-disabled') ?>"
                        <?= $currentStep === 2 ? 'aria-current="step"' : ($currentStep < 2 ? 'aria-disabled="true"' : '') ?>>
                        <span>2</span> Date &amp; Time
                    </li>
                    <li class="<?= $currentStep > 3 ? 'is-complete' : ($currentStep === 3 ? 'is-active' : 'is-disabled') ?>"
                        <?= $currentStep === 3 ? 'aria-current="step"' : ($currentStep < 3 ? 'aria-disabled="true"' : '') ?>>
                        <span>3</span> Your Details
                    </li>
                    <li class="<?= $currentStep === 4 ? 'is-active' : 'is-disabled' ?>"
                        <?= $currentStep === 4 ? 'aria-current="step"' : 'aria-disabled="true"' ?>>
                        <span>4</span> Review
                    </li>
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

            <section class="booking-step <?= $currentStep === 3 ? 'is-active' : ($currentStep > 3 ? 'is-complete' : 'is-disabled') ?>"
                <?= $currentStep < 3 ? 'aria-disabled="true"' : '' ?> aria-labelledby="details-heading">
                <p class="eyebrow">Step 3</p>
                <h2 id="details-heading">Your details</h2>
                <?php if ($selectedSlot === null) : ?>
                    <p>Choose an available time to enter your contact details.</p>
                <?php elseif ($reviewReady) : ?>
                    <p>Your contact details are ready for review.</p>
                <?php else : ?>
                    <?php if ($formErrors !== []) : ?>
                        <div class="error-summary" role="alert" tabindex="-1" data-booking-focus>
                            <h3>Please correct the following</h3>
                            <ul>
                            <?php foreach ($formErrors as $field => $message) : ?>
                                <li>
                                    <?= isset($customer[$field]) ? '<a href="#customer-' . $field . '">' : '' ?>
                                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                                    <?= isset($customer[$field]) ? '</a>' : '' ?>
                                </li>
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
                        <?php
                        $fields = [
                            'name' => ['Full name', 'text', 120],
                            'email' => ['Email address', 'email', 254],
                            'phone' => ['Phone number', 'tel', 32],
                        ];
                        ?>
                        <?php foreach ($fields as $field => [$label, $type, $maxLength]) : ?>
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
                            <textarea id="customer-notes" name="notes" maxlength="1000"
                                <?= isset($formErrors['notes'])
                                    ? 'aria-invalid="true" aria-describedby="customer-notes-error"'
                                    : '' ?>><?= htmlspecialchars($customer['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            <?php if (isset($formErrors['notes'])) : ?>
                                <p id="customer-notes-error" class="field-error">
                                    <?= htmlspecialchars($formErrors['notes'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <button class="button" type="submit">Review booking</button>
                    </form>
                <?php endif; ?>
            </section>
            <section class="booking-step <?= $reviewReady ? 'is-active' : 'is-disabled' ?>"
                <?= !$reviewReady ? 'aria-disabled="true"' : '' ?> aria-labelledby="review-heading">
                <p class="eyebrow">Step 4</p>
                <h2 id="review-heading" <?= $reviewReady ? 'tabindex="-1" data-booking-focus' : '' ?>>Review</h2>
                <?php if (!$reviewReady) : ?>
                    <p>Enter valid contact details to review your booking.</p>
                <?php else : ?>
                    <div class="review-summary">
                        <dl>
                            <div><dt>Service</dt><dd><?= htmlspecialchars($service->name, ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Therapist</dt><dd><?= htmlspecialchars($therapistPreference, ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Date</dt><dd><?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Time</dt><dd><?= $selectedSlot->startsAt->format('g:i A') ?></dd></div>
                            <div><dt>Duration</dt><dd><?= $service->durationMinutes ?> minutes</dd></div>
                            <div><dt>Price</dt><dd>$<?= number_format($service->priceCents / 100, 2) ?></dd></div>
                            <div><dt>Name</dt><dd><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Email</dt><dd><?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <div><dt>Phone</dt><dd><?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <?php if ($customer['notes'] !== '') : ?>
                                <div><dt>Notes</dt><dd><?= htmlspecialchars($customer['notes'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <p><strong>Your appointment has not been booked yet.</strong></p>
                    <button class="button" type="button" disabled>Confirm booking — coming next</button>
                <?php endif; ?>
            </section>
        </div>
    </section>
<?php endif; ?>
