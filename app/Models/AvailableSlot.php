<?php

declare(strict_types=1);

namespace SpaBooking\Models;

use DateTimeImmutable;

final readonly class AvailableSlot
{
    /** @param list<int> $therapistIds */
    public function __construct(
        public DateTimeImmutable $startsAt,
        public array $therapistIds
    ) {
    }
}
