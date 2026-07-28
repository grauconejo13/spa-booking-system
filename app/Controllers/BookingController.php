<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use SpaBooking\Http\Response;
use SpaBooking\Models\Therapist;
use SpaBooking\Repositories\AppointmentScheduleRepository;
use SpaBooking\Repositories\AvailabilityRepository;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\AvailabilityService;
use SpaBooking\Services\BookingDraftStore;
use SpaBooking\Validation\CustomerDetailsValidator;
use SpaBooking\Validation\TimeSelectionValidator;
use SpaBooking\View\ViewRenderer;
use Throwable;

final class BookingController extends Controller
{
    public function __construct(
        ViewRenderer $views,
        private readonly ServiceCatalogRepository $services,
        private readonly TherapistCatalogRepository $therapists,
        private readonly AvailabilityRepository $availability,
        private readonly AppointmentScheduleRepository $appointments,
        private readonly AvailabilityService $availabilityService,
        private readonly DateTimeZone $timezone,
        private readonly CsrfTokenManager $csrf,
        private readonly CustomerDetailsValidator $customerValidator,
        private readonly TimeSelectionValidator $timeValidator,
        private readonly BookingDraftStore $drafts,
        private readonly ?DateTimeImmutable $today = null
    ) {
        parent::__construct($views);
    }

    /** @param array<string, mixed> $query */
    public function start(string $serviceId, array $query = []): Response
    {
        try {
            $data = $this->prepare($serviceId, $query);

            if ($data instanceof Response) {
                return $data;
            }

            $data = $this->withCustomerState($data, $this->drafts->get((int) $data['service']->id));
            $data['activeStep'] = $this->resolveGetStep($query, $data);

            return $this->render('booking-entry', $data);
        } catch (Throwable) {
            return $this->failure();
        }
    }

    /** @param array<string, mixed> $input */
    public function review(string $serviceId, array $input): Response
    {
        try {
            $data = $this->prepare($serviceId, $input);

            if ($data instanceof Response) {
                return $data;
            }

            $validation = $this->customerValidator->validate($input);
            $this->drafts->put((int) $data['service']->id, $validation['values']);
            $data = $this->withCustomerState($data, $validation['values'], $validation['errors']);

            if (!$this->csrf->validate($input['_token'] ?? null)) {
                $data['formErrors']['csrf'] = 'Your session check failed. Please try again.';
                $data['activeStep'] = $data['selectedSlot'] !== null
                    ? 'details'
                    : ($data['hasTherapistSelection'] ? 'datetime' : 'therapist');
                return $this->render('booking-entry', $data, 419);
            }

            if ($data['selectedSlot'] === null) {
                $data['activeStep'] = $data['hasTherapistSelection'] ? 'datetime' : 'therapist';
                return $this->render('booking-entry', $data, 422);
            }

            $requestedStep = is_string($input['step'] ?? null) ? $input['step'] : 'review';
            if ($requestedStep === 'datetime') {
                $data['formErrors'] = [];
                $data['activeStep'] = 'datetime';
                return $this->render('booking-entry', $data);
            }

            if ($requestedStep === 'details') {
                $data['formErrors'] = [];
                $data['activeStep'] = 'details';
                return $this->render('booking-entry', $data);
            }

            $data['reviewReady'] = $data['formErrors'] === [];
            $data['activeStep'] = $data['reviewReady'] ? 'review' : 'details';

            return $this->render('booking-entry', $data, $data['reviewReady'] ? 200 : 422);
        } catch (Throwable) {
            return $this->failure();
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|Response
     */
    private function prepare(string $serviceId, array $input): array|Response
    {
        $id = filter_var($serviceId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!is_int($id)) {
            return $this->notFound();
        }

        $service = $this->services->findActiveById($id);

        if ($service === null) {
            return $this->notFound();
        }

        $therapists = $this->therapists->findActiveQualifiedForService($id);
        $requestedTherapist = is_string($input['therapist'] ?? null) ? $input['therapist'] : null;
        $selectedTherapist = $requestedTherapist ?? 'any';
        $hasTherapistSelection = $requestedTherapist === 'any' && $therapists !== [];
        $qualifiedIds = array_map(static fn (Therapist $therapist): int => $therapist->id, $therapists);

        if ($selectedTherapist !== 'any') {
            $selectedId = filter_var($selectedTherapist, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!is_int($selectedId) || !in_array($selectedId, $qualifiedIds, true)) {
                $selectedTherapist = 'any';
            } else {
                $hasTherapistSelection = true;
            }
        }

        $selectedDate = is_string($input['date'] ?? null) ? $input['date'] : '';
        $selectedTime = is_string($input['time'] ?? null) ? $input['time'] : '';
        $date = $this->parseDate($selectedDate);
        $dateError = null;
        $timeError = null;
        $selectedSlot = null;
        $slots = [];
        $therapistStates = [];

        if ($selectedDate !== '' && $date === null) {
            $dateError = 'Choose a valid date in YYYY-MM-DD format.';
        } elseif ($date !== null && $date < $this->currentDate()) {
            $dateError = 'Choose today or a future date.';
        } elseif ($date !== null && $therapists !== [] && $hasTherapistSelection) {
            $recurring = [];
            $exceptions = [];
            $blocking = [];
            $dayStart = $date->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
            $dayEnd = $date->modify('+1 day')->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));

            foreach ($therapists as $therapist) {
                $recurring[$therapist->id] = $this->availability->findAvailability(
                    $therapist->id,
                    (int) $date->format('N')
                );
                $exceptions[$therapist->id] = $this->availability->findAvailabilityExceptions(
                    $therapist->id,
                    $date->format('Y-m-d')
                );
                $blocking[$therapist->id] = $this->appointments->findOverlappingAppointments(
                    $therapist->id,
                    $dayStart,
                    $dayEnd
                );
            }

            $therapistStates = $this->availabilityService->states(
                $date,
                $service->durationMinutes,
                $therapists,
                $recurring,
                $exceptions,
                $blocking
            );
            $candidateIds = $selectedTherapist === 'any' ? $qualifiedIds : [(int) $selectedTherapist];
            $slots = $this->availabilityService->mergeStateSlots($therapistStates, $candidateIds);
        }

        $timeValidation = $this->timeValidator->validate($selectedTime, $slots);
        $selectedSlot = $timeValidation['slot'];
        $timeError = $timeValidation['error'];

        return [
            'title' => 'Start booking: ' . $service->name,
            'service' => $service,
            'therapists' => $therapists,
            'bookingError' => false,
            'selectedTherapist' => $selectedTherapist,
            'selectedDate' => $selectedDate,
            'dateError' => $dateError,
            'slots' => $slots,
            'selectedTime' => $selectedTime,
            'timeError' => $timeError,
            'selectedSlot' => $selectedSlot,
            'hasTherapistSelection' => $hasTherapistSelection,
            'therapistStates' => $therapistStates,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array{name: string, email: string, phone: string, notes: string}|null $values
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    private function withCustomerState(array $data, ?array $values = null, array $errors = []): array
    {
        $data['customer'] = $values ?? ['name' => '', 'email' => '', 'phone' => '', 'notes' => ''];
        $data['formErrors'] = $errors;
        $data['csrfToken'] = $this->csrf->token();
        $data['reviewReady'] = false;

        return $data;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $data
     */
    private function resolveGetStep(array $query, array $data): string
    {
        $requested = is_string($query['step'] ?? null) ? $query['step'] : 'therapist';

        if (!$data['hasTherapistSelection']) {
            return 'therapist';
        }

        if ($requested === 'therapist') {
            return 'therapist';
        }

        if ($data['selectedSlot'] === null) {
            return 'datetime';
        }

        return $requested === 'details' || $requested === 'review' ? 'details' : 'datetime';
    }

    private function failure(): Response
    {
        return $this->render('booking-entry', ['title' => 'Booking unavailable', 'bookingError' => true], 503);
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->timezone);
        $errors = DateTimeImmutable::getLastErrors();
        $isValid = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

        return $date !== false && $isValid ? $date : null;
    }

    private function currentDate(): DateTimeImmutable
    {
        return ($this->today ?? new DateTimeImmutable('now', $this->timezone))->setTime(0, 0);
    }

    private function notFound(): Response
    {
        return $this->render('errors/404', ['title' => 'Page not found'], 404);
    }
}
