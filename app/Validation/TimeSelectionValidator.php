<?php

declare(strict_types=1);

namespace SpaBooking\Validation;

use SpaBooking\Models\AvailableSlot;

final class TimeSelectionValidator
{
    /**
     * @param list<AvailableSlot> $slots
     * @return array{slot: AvailableSlot|null, error: string|null}
     */
    public function validate(string $selectedTime, array $slots): array
    {
        if ($selectedTime === '') {
            return ['slot' => null, 'error' => null];
        }

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $selectedTime) !== 1) {
            return ['slot' => null, 'error' => 'Choose a valid time in HH:MM format.'];
        }

        foreach ($slots as $slot) {
            if ($slot->startsAt->format('H:i') === $selectedTime) {
                return ['slot' => $slot, 'error' => null];
            }
        }

        return [
            'slot' => null,
            'error' => 'That time is not currently available. Choose one of the listed times.',
        ];
    }
}
