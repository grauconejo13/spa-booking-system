<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use SpaBooking\Models\Appointment;
use SpaBooking\Models\AppointmentInterval;

final class AppointmentRepository implements AppointmentScheduleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function hasBlockingOverlap(
        int $therapistId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): bool {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM appointments
             WHERE therapist_id = :therapist_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
               AND status IN ('pending', 'confirmed')
             LIMIT 1"
        );
        assert($statement !== false);
        $statement->execute([
            'therapist_id' => $therapistId,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findByReference(string $reference): ?Appointment
    {
        $statement = $this->pdo->prepare(
            'SELECT id, reference, service_id, therapist_id, service_name, duration_minutes, price_cents,
                    customer_name, customer_email, customer_phone, customer_note, starts_at, ends_at, status
             FROM appointments WHERE reference = :reference LIMIT 1'
        );
        assert($statement !== false);
        $statement->execute(['reference' => $reference]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return new Appointment(
            (int) $row['id'],
            (string) $row['reference'],
            (int) $row['service_id'],
            (int) $row['therapist_id'],
            (string) $row['service_name'],
            (int) $row['duration_minutes'],
            (int) $row['price_cents'],
            (string) $row['customer_name'],
            (string) $row['customer_email'],
            isset($row['customer_phone']) ? (string) $row['customer_phone'] : null,
            isset($row['customer_note']) ? (string) $row['customer_note'] : null,
            new DateTimeImmutable((string) $row['starts_at'], new DateTimeZone('UTC')),
            new DateTimeImmutable((string) $row['ends_at'], new DateTimeZone('UTC')),
            (string) $row['status']
        );
    }

    public function findOverlappingAppointments(
        int $therapistId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): array {
        $statement = $this->pdo->prepare(
            'SELECT starts_at, ends_at, status FROM appointments
             WHERE therapist_id = :therapist_id AND starts_at < :ends_at AND ends_at > :starts_at
             ORDER BY starts_at, id'
        );
        assert($statement !== false);
        $statement->execute([
            'therapist_id' => $therapistId,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        ]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return array_map(
            static fn (array $row): AppointmentInterval => new AppointmentInterval(
                new DateTimeImmutable((string) $row['starts_at'], new DateTimeZone('UTC')),
                new DateTimeImmutable((string) $row['ends_at'], new DateTimeZone('UTC')),
                (string) $row['status']
            ),
            $rows
        );
    }
}
