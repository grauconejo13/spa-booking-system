<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use PDO;

final class AdminAppointmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function upcoming(int $limit = 25): array
    {
        $limit = max(1, min($limit, 100));
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.reference, a.service_name, a.customer_name, a.starts_at, a.ends_at,
                    a.status, a.price_cents, t.name AS therapist_name
             FROM appointments a
             INNER JOIN therapists t ON t.id = a.therapist_id
             WHERE a.starts_at >= UTC_TIMESTAMP()
             ORDER BY a.starts_at ASC, a.id ASC
             LIMIT :limit'
        );
        assert($statement !== false);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();
        return $rows;
    }
}
