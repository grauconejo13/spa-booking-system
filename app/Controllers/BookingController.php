<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use SpaBooking\Http\Response;
use SpaBooking\Repositories\AppointmentScheduleRepository;
use SpaBooking\Repositories\AvailabilityRepository;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\Services\AvailabilityService;
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
        private readonly ?DateTimeImmutable $today = null
    ) {
        parent::__construct($views);
    }

    /** @param array<string, mixed> $query */
    public function start(string $serviceId, array $query = []): Response
    {
        $id = filter_var($serviceId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!is_int($id)) {
            return $this->notFound();
        }

        try {
            $service = $this->services->findActiveById($id);

            if ($service === null) {
                return $this->notFound();
            }

            $therapists = $this->therapists->findActiveQualifiedForService($id);

            $selectedTherapist = is_string($query['therapist'] ?? null) ? $query['therapist'] : 'any';
            $qualifiedIds = array_map(static fn ($therapist): int => $therapist->id, $therapists);

            if ($selectedTherapist !== 'any') {
                $selectedId = filter_var($selectedTherapist, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (!is_int($selectedId) || !in_array($selectedId, $qualifiedIds, true)) {
                    $selectedTherapist = 'any';
                }
            }

            $selectedDate = is_string($query['date'] ?? null) ? $query['date'] : '';
            $date = $this->parseDate($selectedDate);
            $dateError = null;
            $slots = [];

            if ($selectedDate !== '' && $date === null) {
                $dateError = 'Choose a valid date in YYYY-MM-DD format.';
            } elseif ($date !== null && $date < $this->currentDate()) {
                $dateError = 'Choose today or a future date.';
            } elseif ($date !== null && $therapists !== []) {
                $candidates = $selectedTherapist === 'any'
                    ? $therapists
                    : array_values(array_filter(
                        $therapists,
                        static fn ($therapist): bool => $therapist->id === (int) $selectedTherapist
                    ));
                $recurring = [];
                $exceptions = [];
                $blocking = [];
                $dayStart = $date->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
                $dayEnd = $date->modify('+1 day')->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));

                foreach ($candidates as $therapist) {
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

                $slots = $this->availabilityService->calculate(
                    $date,
                    $service->durationMinutes,
                    $candidates,
                    $recurring,
                    $exceptions,
                    $blocking
                );
            }
        } catch (Throwable) {
            return $this->render('booking-entry', [
                'title' => 'Booking unavailable',
                'service' => null,
                'therapists' => [],
                'bookingError' => true,
                'selectedTherapist' => 'any',
                'selectedDate' => '',
                'dateError' => null,
                'slots' => [],
            ], 503);
        }

        return $this->render('booking-entry', [
            'title' => 'Start booking: ' . $service->name,
            'service' => $service,
            'therapists' => $therapists,
            'bookingError' => false,
            'selectedTherapist' => $selectedTherapist,
            'selectedDate' => $selectedDate,
            'dateError' => $dateError,
            'slots' => $slots,
        ]);
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->timezone);
        $errors = DateTimeImmutable::getLastErrors();

        $isValid = $errors === false
            || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

        return $date !== false && $isValid
            ? $date
            : null;
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
