<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use SpaBooking\Models\Therapist;

interface BookingTherapistRepository extends TherapistCatalogRepository
{
    /** @return list<Therapist> */
    public function lockActiveQualifiedForService(int $serviceId): array;
}
