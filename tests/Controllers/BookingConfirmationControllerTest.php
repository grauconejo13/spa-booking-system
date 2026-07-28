<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use SpaBooking\Controllers\BookingConfirmationController;
use SpaBooking\Models\Appointment;
use SpaBooking\Repositories\AppointmentBookingRepository;
use SpaBooking\View\ViewRenderer;

final class BookingConfirmationControllerTest extends TestCase
{
    public function testItShowsPublicSummaryInSpaTimezoneWithoutSensitiveDetails(): void
    {
        $appointment = new Appointment(
            99,
            'SPA-7K4M9Q2X',
            7,
            3,
            'Forest Facial',
            50,
            8650,
            'Avery Reed',
            'avery@example.test',
            '555-0102',
            null,
            new DateTimeImmutable('2030-06-03 14:00:00', new DateTimeZone('UTC')),
            new DateTimeImmutable('2030-06-03 14:50:00', new DateTimeZone('UTC')),
            'pending',
            'Mara Vale'
        );
        $response = $this->controller($appointment)->show('SPA-7K4M9Q2X');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Your appointment request has been received.', $response->body());
        self::assertStringContainsString('Mara Vale', $response->body());
        self::assertStringContainsString('9:00 AM', $response->body());
        self::assertStringNotContainsString('avery@example.test', $response->body());
        self::assertStringNotContainsString('555-0102', $response->body());
        self::assertStringNotContainsString('>99<', $response->body());
    }

    public function testUnknownOrMalformedReferenceReturnsNotFound(): void
    {
        self::assertSame(404, $this->controller(null)->show('SPA-7K4M9Q2X')->status());
        self::assertSame(404, $this->controller(null)->show('99')->status());
    }

    private function controller(?Appointment $appointment): BookingConfirmationController
    {
        $repository = new class ($appointment) implements AppointmentBookingRepository {
            public function __construct(private readonly ?Appointment $appointment)
            {
            }

            public function beginTransaction(): void
            {
            }

            public function commit(): void
            {
            }

            public function rollBack(): void
            {
            }

            public function countBlockingForDate(
                int $therapistId,
                DateTimeImmutable $start,
                DateTimeImmutable $end
            ): int {
                return 0;
            }

            public function create(
                string $reference,
                int $serviceId,
                int $therapistId,
                string $serviceName,
                int $durationMinutes,
                int $priceCents,
                array $customer,
                DateTimeImmutable $startsAt,
                DateTimeImmutable $endsAt
            ): void {
            }

            public function findByReference(string $reference): ?Appointment
            {
                return $this->appointment;
            }

            public function findOverlappingAppointments(
                int $therapistId,
                DateTimeImmutable $startsAt,
                DateTimeImmutable $endsAt
            ): array {
                return [];
            }
        };

        return new BookingConfirmationController(
            new ViewRenderer(dirname(__DIR__, 2) . '/app/Views'),
            $repository,
            new DateTimeZone('America/Chicago')
        );
    }
}
