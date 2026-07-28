<?php

declare(strict_types=1);

namespace SpaBooking\Services;

use DateInterval;
use DateTimeImmutable;
use SpaBooking\Models\AppointmentInterval;
use SpaBooking\Models\AvailableSlot;
use SpaBooking\Models\Therapist;
use SpaBooking\Models\TherapistAvailability;
use SpaBooking\Models\TherapistAvailabilityException;
use SpaBooking\Models\TherapistAvailabilityState;

final class AvailabilityService
{
    public function __construct(private readonly int $slotIntervalMinutes = 30)
    {
    }

    /**
     * @param list<Therapist> $therapists
     * @param array<int, list<TherapistAvailability>> $recurring
     * @param array<int, list<TherapistAvailabilityException>> $exceptions
     * @param array<int, list<AppointmentInterval>> $appointments
     * @return list<AvailableSlot>
     */
    public function calculate(
        DateTimeImmutable $date,
        int $durationMinutes,
        array $therapists,
        array $recurring,
        array $exceptions,
        array $appointments
    ): array {
        /** @var array<string, array{startsAt: DateTimeImmutable, therapistIds: list<int>}> $merged */
        $merged = [];

        foreach ($therapists as $therapist) {
            $windows = $this->effectiveWindows(
                $recurring[$therapist->id] ?? [],
                $exceptions[$therapist->id] ?? []
            );

            foreach ($windows as [$startsAt, $endsAt]) {
                $cursor = $this->atLocalTime($date, $startsAt);
                $windowEnd = $this->atLocalTime($date, $endsAt);
                $duration = new DateInterval('PT' . $durationMinutes . 'M');
                $interval = new DateInterval('PT' . $this->slotIntervalMinutes . 'M');

                while ($cursor->add($duration) <= $windowEnd) {
                    $slotEnd = $cursor->add($duration);

                    if (!$this->isBlocked($cursor, $slotEnd, $appointments[$therapist->id] ?? [])) {
                        $key = $cursor->format('H:i');
                        $merged[$key] ??= ['startsAt' => $cursor, 'therapistIds' => []];
                        $merged[$key]['therapistIds'][] = $therapist->id;
                    }

                    $cursor = $cursor->add($interval);
                }
            }
        }

        ksort($merged);

        return array_map(
            static fn (array $slot): AvailableSlot => new AvailableSlot(
                $slot['startsAt'],
                array_values(array_unique($slot['therapistIds']))
            ),
            array_values($merged)
        );
    }

    /**
     * @param list<Therapist> $therapists
     * @param array<int, list<TherapistAvailability>> $recurring
     * @param array<int, list<TherapistAvailabilityException>> $exceptions
     * @param array<int, list<AppointmentInterval>> $appointments
     * @return array<int, TherapistAvailabilityState>
     */
    public function states(
        DateTimeImmutable $date,
        int $durationMinutes,
        array $therapists,
        array $recurring,
        array $exceptions,
        array $appointments
    ): array {
        $states = [];

        foreach ($therapists as $therapist) {
            $therapistRecurring = $recurring[$therapist->id] ?? [];
            $therapistExceptions = $exceptions[$therapist->id] ?? [];
            $closed = $therapistExceptions !== []
                && array_filter(
                    $therapistExceptions,
                    static fn (TherapistAvailabilityException $exception): bool => $exception->isAvailable
                ) === [];

            if ($closed) {
                $states[$therapist->id] = new TherapistAvailabilityState(
                    $therapist->id,
                    TherapistAvailabilityState::UNAVAILABLE,
                    []
                );
                continue;
            }

            if ($therapistExceptions === [] && $therapistRecurring === []) {
                $states[$therapist->id] = new TherapistAvailabilityState(
                    $therapist->id,
                    TherapistAvailabilityState::NOT_SCHEDULED,
                    []
                );
                continue;
            }

            $slots = $this->calculate(
                $date,
                $durationMinutes,
                [$therapist],
                [$therapist->id => $therapistRecurring],
                [$therapist->id => $therapistExceptions],
                [$therapist->id => $appointments[$therapist->id] ?? []]
            );
            $status = $slots === []
                ? TherapistAvailabilityState::FULLY_BOOKED
                : TherapistAvailabilityState::AVAILABLE;
            $states[$therapist->id] = new TherapistAvailabilityState($therapist->id, $status, $slots);
        }

        return $states;
    }

    /**
     * @param array<int, TherapistAvailabilityState> $states
     * @param list<int> $therapistIds
     * @return list<AvailableSlot>
     */
    public function mergeStateSlots(array $states, array $therapistIds): array
    {
        /** @var array<string, array{startsAt: DateTimeImmutable, therapistIds: list<int>}> $merged */
        $merged = [];

        foreach ($therapistIds as $therapistId) {
            foreach ($states[$therapistId]->slots ?? [] as $slot) {
                $key = $slot->startsAt->format('H:i');
                $merged[$key] ??= ['startsAt' => $slot->startsAt, 'therapistIds' => []];
                $merged[$key]['therapistIds'] = array_merge($merged[$key]['therapistIds'], $slot->therapistIds);
            }
        }

        ksort($merged);

        return array_map(
            static fn (array $slot): AvailableSlot => new AvailableSlot(
                $slot['startsAt'],
                array_values(array_unique($slot['therapistIds']))
            ),
            array_values($merged)
        );
    }

    /**
     * @param list<TherapistAvailability> $recurring
     * @param list<TherapistAvailabilityException> $exceptions
     * @return list<array{string, string}>
     */
    private function effectiveWindows(array $recurring, array $exceptions): array
    {
        if ($exceptions !== []) {
            $windows = [];

            foreach ($exceptions as $exception) {
                if ($exception->isAvailable && $exception->startsAt !== null && $exception->endsAt !== null) {
                    $windows[] = [$exception->startsAt, $exception->endsAt];
                }
            }

            return $windows;
        }

        return array_map(
            static fn (TherapistAvailability $window): array => [$window->startsAt, $window->endsAt],
            $recurring
        );
    }

    private function atLocalTime(DateTimeImmutable $date, string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($date->format('Y-m-d') . ' ' . $time, $date->getTimezone());
    }

    /** @param list<AppointmentInterval> $appointments */
    private function isBlocked(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt, array $appointments): bool
    {
        $utcStartsAt = $startsAt->setTimezone(new \DateTimeZone('UTC'));
        $utcEndsAt = $endsAt->setTimezone(new \DateTimeZone('UTC'));

        foreach ($appointments as $appointment) {
            if (!in_array($appointment->status, ['pending', 'confirmed'], true)) {
                continue;
            }

            if ($appointment->startsAt < $utcEndsAt && $appointment->endsAt > $utcStartsAt) {
                return true;
            }
        }

        return false;
    }
}
