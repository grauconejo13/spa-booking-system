<?php

declare(strict_types=1);

namespace SpaBooking\Models;

final readonly class TherapistAvailability
{
    public function __construct(
        public int $id,
        public int $therapistId,
        public int $dayOfWeek,
        public string $startsAt,
        public string $endsAt
    ) {
    }
}
