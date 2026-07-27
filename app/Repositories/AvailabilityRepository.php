<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use SpaBooking\Models\TherapistAvailability;
use SpaBooking\Models\TherapistAvailabilityException;

interface AvailabilityRepository
{
    /** @return list<TherapistAvailability> */
    public function findAvailability(int $therapistId, int $dayOfWeek): array;

    /** @return list<TherapistAvailabilityException> */
    public function findAvailabilityExceptions(int $therapistId, string $date): array;
}
