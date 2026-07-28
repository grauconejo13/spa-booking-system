<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Services;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Models\AppointmentInterval;
use SpaBooking\Services\AvailabilityService;
use SpaBooking\Services\BookingConflictException;
use SpaBooking\Services\BookingReferenceGenerator;
use SpaBooking\Services\BookingService;
use SpaBooking\Services\TherapistAssignmentService;
use SpaBooking\Tests\Support\FakeBookingAppointments;
use SpaBooking\Tests\Support\FakeBookingServices;
use SpaBooking\Tests\Support\FakeBookingTherapists;

final class BookingServiceTest extends TestCase
{
    public function testItAssignsByWorkloadAndPersistsPendingUtcSnapshot(): void
    {
        $appointments = new FakeBookingAppointments();
        $appointments->counts = [3 => 2, 7 => 1];
        $service = $this->service($appointments);

        $reference = $service->book(5, 'any', '2030-06-03', '09:00', $this->customer());

        self::assertMatchesRegularExpression('/^SPA-[23456789A-HJ-NP-Z]{8}$/', $reference);
        self::assertTrue($appointments->committed);
        self::assertSame(7, $appointments->created['therapistId']);
        self::assertSame('Forest Facial', $appointments->created['serviceName']);
        self::assertSame('2030-06-03 14:00:00', $appointments->created['startsAt']->format('Y-m-d H:i:s'));
        self::assertSame('2030-06-03 14:50:00', $appointments->created['endsAt']->format('Y-m-d H:i:s'));
    }

    public function testSpecificTherapistAndExactBoundaryRemainAvailable(): void
    {
        $appointments = new FakeBookingAppointments();
        $appointments->intervals[3] = [new AppointmentInterval(
            new DateTimeImmutable('2030-06-03 13:00:00', new DateTimeZone('UTC')),
            new DateTimeImmutable('2030-06-03 14:00:00', new DateTimeZone('UTC')),
            'confirmed'
        )];

        $this->service($appointments)->book(5, '3', '2030-06-03', '09:00', $this->customer());

        self::assertSame(3, $appointments->created['therapistId']);
    }

    public function testOverlapOrInsertionFailureRollsBack(): void
    {
        $blocked = new FakeBookingAppointments();
        $blocked->intervals[3] = [new AppointmentInterval(
            new DateTimeImmutable('2030-06-03 14:15:00', new DateTimeZone('UTC')),
            new DateTimeImmutable('2030-06-03 15:00:00', new DateTimeZone('UTC')),
            'pending'
        )];
        $blocked->intervals[7] = $blocked->intervals[3];

        try {
            $this->service($blocked)->book(5, 'any', '2030-06-03', '09:00', $this->customer());
            self::fail('Expected a conflict.');
        } catch (BookingConflictException) {
            self::assertTrue($blocked->rolledBack);
        }

        $failed = new FakeBookingAppointments();
        $failed->failInsert = true;
        try {
            $this->service($failed)->book(5, '3', '2030-06-03', '09:00', $this->customer());
            self::fail('Expected insertion failure.');
        } catch (RuntimeException) {
            self::assertTrue($failed->rolledBack);
        }
    }

    private function service(FakeBookingAppointments $appointments): BookingService
    {
        return new BookingService(
            new FakeBookingServices(),
            new FakeBookingTherapists(),
            new FakeBookingTherapists(),
            $appointments,
            new AvailabilityService(),
            new TherapistAssignmentService(),
            new BookingReferenceGenerator(),
            new DateTimeZone('America/Chicago'),
            new DateTimeImmutable('2030-06-01', new DateTimeZone('America/Chicago'))
        );
    }

    /** @return array{name: string, email: string, phone: string, notes: string} */
    private function customer(): array
    {
        return ['name' => 'Avery Reed', 'email' => 'avery@example.test', 'phone' => '555-0102', 'notes' => ''];
    }
}
