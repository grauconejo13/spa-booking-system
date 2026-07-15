<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use PDO;
use SpaBooking\Models\Service;

final class ServiceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<Service> */
    public function findActive(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, slug, description, duration_minutes, price_cents, is_active, display_order
             FROM services WHERE is_active = 1 ORDER BY display_order, id'
        );
        assert($statement !== false);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return array_map($this->map(...), $rows);
    }

    public function findActiveBySlug(string $slug): ?Service
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, slug, description, duration_minutes, price_cents, is_active, display_order
             FROM services WHERE slug = :slug AND is_active = 1 LIMIT 1'
        );
        assert($statement !== false);
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Service
    {
        return new Service(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['slug'],
            (string) $row['description'],
            (int) $row['duration_minutes'],
            (int) $row['price_cents'],
            (bool) $row['is_active'],
            (int) $row['display_order']
        );
    }
}
