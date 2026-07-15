<?php

declare(strict_types=1);

namespace SpaBooking\Models;

final readonly class Therapist
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $bio,
        public bool $isActive,
        public int $displayOrder
    ) {
    }
}
