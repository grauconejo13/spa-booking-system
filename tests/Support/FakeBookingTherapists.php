<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Support;

use SpaBooking\Models\Therapist;
use SpaBooking\Models\TherapistAvailability;
use SpaBooking\Repositories\AvailabilityRepository;
use SpaBooking\Repositories\BookingTherapistRepository;

final class FakeBookingTherapists implements BookingTherapistRepository, AvailabilityRepository
{
    public function findActiveQualifiedForService(int $serviceId): array
    {
        return $this->therapists();
    }

    public function lockActiveQualifiedForService(int $serviceId): array
    {
        return $this->therapists();
    }

    public function findAvailability(int $therapistId, int $dayOfWeek): array
    {
        return [new TherapistAvailability($therapistId, $therapistId, 1, '09:00', '11:00')];
    }

    public function findAvailabilityExceptions(int $therapistId, string $date): array
    {
        return [];
    }

    /** @return list<Therapist> */
    private function therapists(): array
    {
        return [
            new Therapist(3, 'Mara', 'mara', '', true, 1),
            new Therapist(7, 'Theo', 'theo', '', true, 2),
        ];
    }
}
