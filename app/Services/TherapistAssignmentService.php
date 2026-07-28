<?php

declare(strict_types=1);

namespace SpaBooking\Services;

final class TherapistAssignmentService
{
    /**
     * @param list<int> $candidateIds
     * @param array<int, int> $appointmentCounts
     */
    public function assign(array $candidateIds, array $appointmentCounts): ?int
    {
        usort($candidateIds, static function (int $left, int $right) use ($appointmentCounts): int {
            $countComparison = ($appointmentCounts[$left] ?? 0) <=> ($appointmentCounts[$right] ?? 0);

            return $countComparison !== 0 ? $countComparison : $left <=> $right;
        });

        return $candidateIds[0] ?? null;
    }
}
