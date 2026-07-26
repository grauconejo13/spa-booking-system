<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use SpaBooking\Models\Service;

interface ServiceCatalogRepository
{
    /** @return list<Service> */
    public function findActive(): array;

    public function findActiveById(int $id): ?Service;
}
