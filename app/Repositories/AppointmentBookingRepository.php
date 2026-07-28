<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use DateTimeImmutable;
use SpaBooking\Models\Appointment;

interface AppointmentBookingRepository extends AppointmentScheduleRepository
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function countBlockingForDate(int $therapistId, DateTimeImmutable $start, DateTimeImmutable $end): int;

    /** @param array{name: string, email: string, phone: string, notes: string} $customer */
    public function create(
        string $reference,
        int $serviceId,
        int $therapistId,
        string $serviceName,
        int $durationMinutes,
        int $priceCents,
        array $customer,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): void;

    public function findByReference(string $reference): ?Appointment;
}
