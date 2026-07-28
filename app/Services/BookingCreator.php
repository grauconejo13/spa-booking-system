<?php

declare(strict_types=1);

namespace SpaBooking\Services;

interface BookingCreator
{
    /** @param array{name: string, email: string, phone: string, notes: string} $customer */
    public function book(
        int $serviceId,
        string $therapistPreference,
        string $dateValue,
        string $timeValue,
        array $customer
    ): string;
}
