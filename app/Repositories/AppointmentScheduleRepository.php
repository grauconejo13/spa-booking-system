<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use DateTimeImmutable;
use SpaBooking\Models\AppointmentInterval;

interface AppointmentScheduleRepository
{
    /** @return list<AppointmentInterval> */
    public function findOverlappingAppointments(
        int $therapistId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): array;
}
