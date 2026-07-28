<?php

declare(strict_types=1);

namespace SpaBooking\Services;

final class BookingReferenceGenerator
{
    private const string ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function generate(): string
    {
        $reference = 'SPA-';
        $maximum = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < 8; $index++) {
            $reference .= self::ALPHABET[random_int(0, $maximum)];
        }

        return $reference;
    }
}
