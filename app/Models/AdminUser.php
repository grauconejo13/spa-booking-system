<?php

declare(strict_types=1);

namespace SpaBooking\Models;

final readonly class AdminUser
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public bool $isActive
    ) {
    }
}
