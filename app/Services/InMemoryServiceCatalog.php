<?php

declare(strict_types=1);

namespace SpaBooking\Services;

final class InMemoryServiceCatalog
{
    /** @return list<array{name: string, description: string, durationMinutes: int, priceCents: int}> */
    public function all(): array
    {
        return [
            [
                'name' => 'Meadow Calm Massage',
                'description' => 'A gentle full-body massage designed for quiet relaxation.',
                'durationMinutes' => 60,
                'priceCents' => 9800,
            ],
            [
                'name' => 'Cedar Glow Facial',
                'description' => 'A refreshing facial ritual with cleansing, hydration, and a soothing mask.',
                'durationMinutes' => 50,
                'priceCents' => 8600,
            ],
            [
                'name' => 'Lakeside Restore',
                'description' => 'A focused massage for shoulders, neck, and back after a demanding week.',
                'durationMinutes' => 45,
                'priceCents' => 7600,
            ],
        ];
    }
}

