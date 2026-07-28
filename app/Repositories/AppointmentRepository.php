<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use SpaBooking\Models\Appointment;
use SpaBooking\Models\AppointmentInterval;

final class AppointmentRepository implements AppointmentBookingRepository
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

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function countBlockingForDate(
        int $therapistId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): int {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM appointments
             WHERE therapist_id = :therapist_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
               AND status IN ('pending', 'confirmed')"
        );
        assert($statement !== false);
        $statement->execute([
            'therapist_id' => $therapistId,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $end->format('Y-m-d H:i:s'),
        ]);

        return (int) $statement->fetchColumn();
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
        $statement = $this->pdo->prepare(
            "INSERT INTO appointments (
                reference, service_id, therapist_id, service_name, duration_minutes, price_cents,
                customer_name, customer_email, customer_phone, customer_note, starts_at, ends_at,
                status, created_at, updated_at
            ) VALUES (
                :reference, :service_id, :therapist_id, :service_name, :duration_minutes, :price_cents,
                :customer_name, :customer_email, :customer_phone, :customer_note, :starts_at, :ends_at,
                'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP()
            )"
        );
        assert($statement !== false);
        try {
            $statement->execute([
                'reference' => $reference,
                'service_id' => $serviceId,
                'therapist_id' => $therapistId,
                'service_name' => $serviceName,
                'duration_minutes' => $durationMinutes,
                'price_cents' => $priceCents,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] !== '' ? $customer['phone'] : null,
                'customer_note' => $customer['notes'] !== '' ? $customer['notes'] : null,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new DuplicateBookingReferenceException('Booking reference collision.', 0, $exception);
            }

            throw $exception;
        }
    }

    public function findByReference(string $reference): ?Appointment
    {
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.reference, a.service_id, a.therapist_id, a.service_name, a.duration_minutes,
                    a.price_cents, a.customer_name, a.customer_email, a.customer_phone, a.customer_note,
                    a.starts_at, a.ends_at, a.status, t.name AS therapist_name
             FROM appointments a
             INNER JOIN therapists t ON t.id = a.therapist_id
             WHERE a.reference = :reference LIMIT 1'
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
            (string) $row['status'],
            (string) $row['therapist_name']
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
