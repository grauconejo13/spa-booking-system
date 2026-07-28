<?php

declare(strict_types=1);

namespace SpaBooking\Models;

final readonly class TherapistAvailabilityState
{
    public const string AVAILABLE = 'available';
    public const string NOT_SCHEDULED = 'not_scheduled';
    public const string FULLY_BOOKED = 'fully_booked';
    public const string UNAVAILABLE = 'unavailable';

    /** @param list<AvailableSlot> $slots */
    public function __construct(
        public int $therapistId,
        public string $status,
        public array $slots
    ) {
    }
}
