<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Services;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use SpaBooking\Models\AppointmentInterval;
use SpaBooking\Models\Therapist;
use SpaBooking\Models\TherapistAvailability;
use SpaBooking\Models\TherapistAvailabilityException;
use SpaBooking\Models\TherapistAvailabilityState;
use SpaBooking\Services\AvailabilityService;

final class AvailabilityServiceTest extends TestCase
{
    private AvailabilityService $service;
    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->service = new AvailabilityService(30);
        $this->timezone = new DateTimeZone('America/Chicago');
    }

    public function testItBuildsThirtyMinuteStartsInsideRecurringWindows(): void
    {
        $slots = $this->calculate([$this->therapist(1)], [1 => [$this->window(1, '09:00', '11:00')]]);

        self::assertSame(['9:00 AM', '9:30 AM', '10:00 AM'], $this->times($slots));
    }

    public function testSpecificTherapistResultsContainOnlyThatTherapist(): void
    {
        $slots = $this->calculate([$this->therapist(2)], [2 => [$this->window(2, '09:00', '10:30')]]);

        self::assertSame([2], $slots[0]->therapistIds);
    }

    public function testAnyTherapistMergesDuplicateStartsAndRetainsCandidateIds(): void
    {
        $slots = $this->calculate(
            [$this->therapist(1), $this->therapist(2)],
            [1 => [$this->window(1, '09:00', '10:00')], 2 => [$this->window(2, '09:00', '10:00')]],
            duration: 30
        );

        self::assertCount(2, $slots);
        self::assertSame([1, 2], $slots[0]->therapistIds);
    }

    public function testDateExceptionsReplaceRecurringAvailability(): void
    {
        $exceptions = [1 => [new TherapistAvailabilityException(1, 1, '2030-06-03', true, '13:00', '14:00')]];
        $slots = $this->calculate(
            [$this->therapist(1)],
            [1 => [$this->window(1, '09:00', '11:00')]],
            $exceptions,
            duration: 30
        );

        self::assertSame(['1:00 PM', '1:30 PM'], $this->times($slots));
    }

    public function testClosedDateExceptionProducesNoSlots(): void
    {
        $exceptions = [1 => [new TherapistAvailabilityException(1, 1, '2030-06-03', false, null, null)]];

        self::assertSame([], $this->calculate(
            [$this->therapist(1)],
            [1 => [$this->window(1, '09:00', '11:00')]],
            $exceptions
        ));
    }

    public function testBlockingAppointmentExcludesOverlappingStartsButCancelledDoesNot(): void
    {
        $utc = new DateTimeZone('UTC');
        $appointments = [1 => [
            new AppointmentInterval(
                new DateTimeImmutable('2030-06-03 14:30:00', $utc),
                new DateTimeImmutable('2030-06-03 15:30:00', $utc),
                'confirmed'
            ),
            new AppointmentInterval(
                new DateTimeImmutable('2030-06-03 16:00:00', $utc),
                new DateTimeImmutable('2030-06-03 17:00:00', $utc),
                'cancelled'
            ),
        ]];
        $slots = $this->calculate(
            [$this->therapist(1)],
            [1 => [$this->window(1, '09:00', '12:00')]],
            appointments: $appointments
        );

        self::assertSame(['10:30 AM', '11:00 AM'], $this->times($slots));
    }

    public function testLocalDateUsesSpaTimezoneWhileComparingUtcAppointments(): void
    {
        $slots = $this->calculate([$this->therapist(1)], [1 => [$this->window(1, '09:00', '10:00')]], duration: 30);

        self::assertSame('America/Chicago', $slots[0]->startsAt->getTimezone()->getName());
        $utcStart = $slots[0]->startsAt->setTimezone(new DateTimeZone('UTC'));

        self::assertSame('2030-06-03 14:00', $utcStart->format('Y-m-d H:i'));
    }

    public function testItClassifiesAvailableAndNotScheduledTherapists(): void
    {
        $therapists = [$this->therapist(1), $this->therapist(2)];
        $states = $this->service->states(
            new DateTimeImmutable('2030-06-03', $this->timezone),
            60,
            $therapists,
            [1 => [$this->window(1, '09:00', '11:00')]],
            [],
            []
        );

        self::assertSame(TherapistAvailabilityState::AVAILABLE, $states[1]->status);
        self::assertSame(TherapistAvailabilityState::NOT_SCHEDULED, $states[2]->status);
    }

    public function testItClassifiesDateClosuresAndFullyBookedWindows(): void
    {
        $utc = new DateTimeZone('UTC');
        $therapists = [$this->therapist(1), $this->therapist(2)];
        $states = $this->service->states(
            new DateTimeImmutable('2030-06-03', $this->timezone),
            60,
            $therapists,
            [
                1 => [$this->window(1, '09:00', '10:00')],
                2 => [$this->window(2, '09:00', '10:00')],
            ],
            [1 => [new TherapistAvailabilityException(1, 1, '2030-06-03', false, null, null)]],
            [2 => [new AppointmentInterval(
                new DateTimeImmutable('2030-06-03 14:00', $utc),
                new DateTimeImmutable('2030-06-03 15:00', $utc),
                'confirmed'
            )]]
        );

        self::assertSame(TherapistAvailabilityState::UNAVAILABLE, $states[1]->status);
        self::assertSame(TherapistAvailabilityState::FULLY_BOOKED, $states[2]->status);
    }

    /**
     * @param list<Therapist> $therapists
     * @param array<int, list<TherapistAvailability>> $recurring
     * @param array<int, list<TherapistAvailabilityException>> $exceptions
     * @param array<int, list<AppointmentInterval>> $appointments
     * @return list<\SpaBooking\Models\AvailableSlot>
     */
    private function calculate(
        array $therapists,
        array $recurring,
        array $exceptions = [],
        array $appointments = [],
        int $duration = 60
    ): array {
        return $this->service->calculate(
            new DateTimeImmutable('2030-06-03', $this->timezone),
            $duration,
            $therapists,
            $recurring,
            $exceptions,
            $appointments
        );
    }

    private function therapist(int $id): Therapist
    {
        return new Therapist($id, 'Therapist ' . $id, 'therapist-' . $id, 'Bio', true, $id);
    }

    private function window(int $therapistId, string $startsAt, string $endsAt): TherapistAvailability
    {
        return new TherapistAvailability(1, $therapistId, 1, $startsAt, $endsAt);
    }

    /** @param list<\SpaBooking\Models\AvailableSlot> $slots */
    private function times(array $slots): array
    {
        return array_map(static fn ($slot): string => $slot->startsAt->format('g:i A'), $slots);
    }
}
