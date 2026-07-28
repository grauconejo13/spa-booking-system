<?php

declare(strict_types=1);

namespace SpaBooking\Validation;

final class CustomerDetailsRules
{
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 120;
    public const int EMAIL_MAX_LENGTH = 254;
    public const int PHONE_MIN_LENGTH = 7;
    public const int PHONE_MAX_LENGTH = 32;
    public const int NOTES_MAX_LENGTH = 1000;

    private function __construct()
    {
    }
}
