<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\BookingConflictException;
use SpaBooking\Services\BookingCreator;
use SpaBooking\Services\BookingSubmissionStore;
use SpaBooking\Validation\CustomerDetailsValidator;
use Throwable;

final class BookingSubmissionController
{
    public function __construct(
        private readonly CsrfTokenManager $csrf,
        private readonly CustomerDetailsValidator $customers,
        private readonly BookingCreator $bookings,
        private readonly BookingSubmissionStore $submissions
    ) {
    }

    /** @param array<string, mixed> $input */
    public function confirm(string $serviceId, array $input): Response
    {
        $id = filter_var($serviceId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $token = is_string($input['submission_token'] ?? null) ? $input['submission_token'] : '';

        if (!is_int($id) || !$this->csrf->validate($input['_token'] ?? null) || !$this->submissions->isIssued($token)) {
            return new Response('Booking submission could not be validated.', 422);
        }

        $existing = $this->submissions->reference($token);
        if ($existing !== null) {
            return $this->redirect($existing);
        }

        $validation = $this->customers->validate($input);
        if ($validation['errors'] !== []) {
            return new Response('Please return to the booking form and check your contact details.', 422);
        }

        try {
            $reference = $this->bookings->book(
                $id,
                is_string($input['therapist'] ?? null) ? $input['therapist'] : '',
                is_string($input['date'] ?? null) ? $input['date'] : '',
                is_string($input['time'] ?? null) ? $input['time'] : '',
                $validation['values']
            );
            $this->submissions->complete($token, $reference);

            return $this->redirect($reference);
        } catch (BookingConflictException) {
            $query = http_build_query([
                'step' => 'datetime',
                'therapist' => is_string($input['therapist'] ?? null) ? $input['therapist'] : 'any',
                'date' => is_string($input['date'] ?? null) ? $input['date'] : '',
                'booking_error' => 'stale',
            ]);

            return new Response('', 303, ['Location' => '/book/' . $id . '?' . $query . '#booking-flow']);
        } catch (Throwable) {
            return new Response('We could not create this appointment request. Please try again.', 503);
        }
    }

    private function redirect(string $reference): Response
    {
        return new Response('', 303, ['Location' => '/booking/confirmation/' . rawurlencode($reference)]);
    }
}
