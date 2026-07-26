<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use SpaBooking\Models\Therapist;

interface TherapistCatalogRepository
{
    /** @return list<Therapist> */
    public function findActiveQualifiedForService(int $serviceId): array;
}
