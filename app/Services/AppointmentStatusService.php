<?php

declare(strict_types=1);

namespace SpaBooking\Services;

final class AppointmentStatusService
{
    /** @var array<string, list<string>> */
    private const array TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /** @return list<string> */
    public function allowedTransitions(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    public function canTransition(string $currentStatus, string $nextStatus): bool
    {
        return in_array($nextStatus, $this->allowedTransitions($currentStatus), true);
    }
}
