<?php

declare(strict_types=1);

namespace SpaBooking\Models;

use DateTimeImmutable;

final readonly class Appointment
{
    public function __construct(
        public int $id,
        public string $reference,
        public int $serviceId,
        public int $therapistId,
        public string $serviceName,
        public int $durationMinutes,
        public int $priceCents,
        public string $customerName,
        public string $customerEmail,
        public ?string $customerPhone,
        public ?string $customerNote,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
        public string $status,
        public ?string $therapistName = null
    ) {
    }
}
