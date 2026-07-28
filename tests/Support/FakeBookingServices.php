<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Support;

use SpaBooking\Models\Service;
use SpaBooking\Repositories\ServiceCatalogRepository;

final class FakeBookingServices implements ServiceCatalogRepository
{
    public function findActive(): array
    {
        return [];
    }

    public function findActiveById(int $id): ?Service
    {
        return $id === 5 ? new Service(5, 'Forest Facial', 'forest', 'Calming.', 50, 8650, true, 1) : null;
    }
}
