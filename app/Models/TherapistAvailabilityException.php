<?php

declare(strict_types=1);

namespace SpaBooking\Models;

final readonly class TherapistAvailabilityException
{
    public function __construct(
        public int $id,
        public int $therapistId,
        public string $date,
        public bool $isAvailable,
        public ?string $startsAt,
        public ?string $endsAt
    ) {
    }
}
