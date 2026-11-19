<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use PDO;

final class AdminAppointmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.reference, a.service_name, a.duration_minutes, a.price_cents,
                    a.customer_name, a.customer_email, a.customer_phone, a.customer_note,
                    a.starts_at, a.ends_at, a.status, a.created_at, t.name AS therapist_name
             FROM appointments a
             INNER JOIN therapists t ON t.id = a.therapist_id
             WHERE a.id = :id
             LIMIT 1'
        );
        assert($statement !== false);
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateStatus(int $id, string $expectedStatus, string $nextStatus): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE appointments
             SET status = :next_status
             WHERE id = :id AND status = :expected_status'
        );
        assert($statement !== false);
        $statement->execute([
            'id' => $id,
            'expected_status' => $expectedStatus,
            'next_status' => $nextStatus,
        ]);

        return $statement->rowCount() === 1;
    }

    /** @return list<array<string, mixed>> */
    public function upcoming(?string $status = null, int $limit = 25): array
    {
        $limit = max(1, min($limit, 100));
        $sql =
            'SELECT a.id, a.reference, a.service_name, a.customer_name, a.starts_at, a.ends_at,
                    a.status, a.price_cents, t.name AS therapist_name
             FROM appointments a
             INNER JOIN therapists t ON t.id = a.therapist_id
             WHERE a.starts_at >= UTC_TIMESTAMP()';

        if ($status !== null) {
            $sql .= ' AND a.status = :status';
        }

        $sql .= ' ORDER BY a.starts_at ASC, a.id ASC LIMIT :limit';

        $statement = $this->pdo->prepare($sql);
        assert($statement !== false);

        if ($status !== null) {
            $statement->bindValue('status', $status);
        }
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();
        return $rows;
    }
}
