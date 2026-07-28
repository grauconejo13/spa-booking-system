<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Support;

use DateTimeImmutable;
use RuntimeException;
use SpaBooking\Models\Appointment;
use SpaBooking\Models\AppointmentInterval;
use SpaBooking\Repositories\AppointmentBookingRepository;

final class FakeBookingAppointments implements AppointmentBookingRepository
{
    /** @var array<int, int> */
    public array $counts = [];

    /** @var array<int, list<AppointmentInterval>> */
    public array $intervals = [];

    /** @var array<string, mixed> */
    public array $created = [];

    public bool $committed = false;
    public bool $rolledBack = false;
    public bool $failInsert = false;

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
        $this->committed = true;
    }

    public function rollBack(): void
    {
        $this->rolledBack = true;
    }

    public function countBlockingForDate(int $therapistId, DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        return $this->counts[$therapistId] ?? 0;
    }

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
    ): void {
        if ($this->failInsert) {
            throw new RuntimeException('Insert failed.');
        }

        $this->created = compact('reference', 'therapistId', 'serviceName', 'startsAt', 'endsAt');
    }

    public function findByReference(string $reference): ?Appointment
    {
        return null;
    }

    public function findOverlappingAppointments(
        int $therapistId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): array {
        return $this->intervals[$therapistId] ?? [];
    }
}
