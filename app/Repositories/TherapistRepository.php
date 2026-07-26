<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use PDO;
use SpaBooking\Models\Therapist;
use SpaBooking\Models\TherapistAvailability;

final class TherapistRepository implements TherapistCatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<Therapist> */
    public function findActiveQualifiedForService(int $serviceId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id, t.name, t.slug, t.bio, t.is_active, t.display_order
             FROM therapists t
             INNER JOIN therapist_services ts ON ts.therapist_id = t.id
             WHERE ts.service_id = :service_id AND t.is_active = 1
             ORDER BY t.display_order, t.id'
        );
        assert($statement !== false);
        $statement->execute(['service_id' => $serviceId]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return array_map($this->mapTherapist(...), $rows);
    }

    /** @return list<TherapistAvailability> */
    public function findAvailability(int $therapistId, int $dayOfWeek): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, therapist_id, day_of_week, starts_at, ends_at
             FROM therapist_availability
             WHERE therapist_id = :therapist_id AND day_of_week = :day_of_week
             ORDER BY starts_at, id'
        );
        assert($statement !== false);
        $statement->execute(['therapist_id' => $therapistId, 'day_of_week' => $dayOfWeek]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return array_map(
            static fn (array $row): TherapistAvailability => new TherapistAvailability(
                (int) $row['id'],
                (int) $row['therapist_id'],
                (int) $row['day_of_week'],
                (string) $row['starts_at'],
                (string) $row['ends_at']
            ),
            $rows
        );
    }

    /** @param array<string, mixed> $row */
    private function mapTherapist(array $row): Therapist
    {
        return new Therapist(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['slug'],
            (string) $row['bio'],
            (bool) $row['is_active'],
            (int) $row['display_order']
        );
    }
}
