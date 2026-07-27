<?php

declare(strict_types=1);

namespace SpaBooking\Models;

use DateTimeImmutable;

final readonly class AppointmentInterval
{
    public function __construct(
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
        public string $status
    ) {
    }
}
