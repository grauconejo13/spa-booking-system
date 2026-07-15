<?php

declare(strict_types=1);

namespace SpaBooking\Models;

final readonly class Service
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $description,
        public int $durationMinutes,
        public int $priceCents,
        public bool $isActive,
        public int $displayOrder
    ) {
    }
}
