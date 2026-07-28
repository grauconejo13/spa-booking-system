<?php

declare(strict_types=1);

namespace SpaBooking\Services;

use DateTimeImmutable;
use DateTimeZone;
use SpaBooking\Models\Therapist;
use SpaBooking\Repositories\AppointmentBookingRepository;
use SpaBooking\Repositories\AvailabilityRepository;
use SpaBooking\Repositories\BookingTherapistRepository;
use SpaBooking\Repositories\DuplicateBookingReferenceException;
use SpaBooking\Repositories\ServiceCatalogRepository;
use Throwable;

final class BookingService implements BookingCreator
{
    public function __construct(
        private readonly ServiceCatalogRepository $services,
        private readonly BookingTherapistRepository $therapists,
        private readonly AvailabilityRepository $availability,
        private readonly AppointmentBookingRepository $appointments,
        private readonly AvailabilityService $availabilityService,
        private readonly TherapistAssignmentService $assignment,
        private readonly BookingReferenceGenerator $references,
        private readonly DateTimeZone $timezone,
        private readonly ?DateTimeImmutable $today = null
    ) {
    }

    /** @param array{name: string, email: string, phone: string, notes: string} $customer */
    public function book(
        int $serviceId,
        string $therapistPreference,
        string $dateValue,
        string $timeValue,
        array $customer
    ): string {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $dateValue . ' ' . $timeValue, $this->timezone);

        if ($date === false || $date->format('Y-m-d H:i') !== $dateValue . ' ' . $timeValue) {
            throw new BookingConflictException('Invalid appointment date or time.');
        }

        $currentDate = ($this->today ?? new DateTimeImmutable('now', $this->timezone))->setTime(0, 0);
        if ($date->setTime(0, 0) < $currentDate) {
            throw new BookingConflictException('The appointment date is in the past.');
        }

        $this->appointments->beginTransaction();

        try {
            $service = $this->services->findActiveById($serviceId);
            if ($service === null) {
                throw new BookingConflictException('The selected service is unavailable.');
            }

            $therapists = $this->therapists->lockActiveQualifiedForService($serviceId);
            $states = $this->availabilityStates($date, $service->durationMinutes, $therapists);
            $slot = $date->format('H:i');
            $availableIds = [];

            foreach ($states as $therapistId => $state) {
                foreach ($state->slots as $availableSlot) {
                    if ($availableSlot->startsAt->format('H:i') === $slot) {
                        $availableIds[] = $therapistId;
                        break;
                    }
                }
            }

            $therapistId = $this->selectTherapist($therapistPreference, $availableIds, $date);
            if ($therapistId === null) {
                throw new BookingConflictException('That appointment time is no longer available.');
            }

            $startsAt = $date->setTimezone(new DateTimeZone('UTC'));
            $endsAt = $startsAt->modify('+' . $service->durationMinutes . ' minutes');
            $reference = $this->insertWithUniqueReference(
                $service->id,
                $therapistId,
                $service->name,
                $service->durationMinutes,
                $service->priceCents,
                $customer,
                $startsAt,
                $endsAt
            );
            $this->appointments->commit();

            return $reference;
        } catch (Throwable $exception) {
            $this->appointments->rollBack();
            throw $exception;
        }
    }

    /** @param array{name: string, email: string, phone: string, notes: string} $customer */
    private function insertWithUniqueReference(
        int $serviceId,
        int $therapistId,
        string $serviceName,
        int $durationMinutes,
        int $priceCents,
        array $customer,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): string {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $reference = $this->references->generate();

            try {
                $this->appointments->create(
                    $reference,
                    $serviceId,
                    $therapistId,
                    $serviceName,
                    $durationMinutes,
                    $priceCents,
                    $customer,
                    $startsAt,
                    $endsAt
                );

                return $reference;
            } catch (DuplicateBookingReferenceException) {
                continue;
            }
        }

        throw new DuplicateBookingReferenceException('Could not generate a unique booking reference.');
    }

    /**
     * @param list<Therapist> $therapists
     * @return array<int, \SpaBooking\Models\TherapistAvailabilityState>
     */
    private function availabilityStates(DateTimeImmutable $date, int $duration, array $therapists): array
    {
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

        return $this->availabilityService->states(
            $date->setTime(0, 0),
            $duration,
            $therapists,
            $recurring,
            $exceptions,
            $blocking
        );
    }

    /** @param list<int> $availableIds */
    private function selectTherapist(
        string $preference,
        array $availableIds,
        DateTimeImmutable $date
    ): ?int {
        if ($preference !== 'any') {
            $id = filter_var($preference, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            return is_int($id) && in_array($id, $availableIds, true) ? $id : null;
        }

        $start = $date->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
        $end = $date->modify('+1 day')->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
        $counts = [];
        foreach ($availableIds as $id) {
            $counts[$id] = $this->appointments->countBlockingForDate($id, $start, $end);
        }

        return $this->assignment->assign($availableIds, $counts);
    }
}
