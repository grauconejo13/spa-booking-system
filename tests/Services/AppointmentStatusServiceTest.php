<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use SpaBooking\Services\AppointmentStatusService;

final class AppointmentStatusServiceTest extends TestCase
{
    public function testPendingCanBeConfirmedOrCancelled(): void
    {
        $service = new AppointmentStatusService();

        self::assertTrue($service->canTransition('pending', 'confirmed'));
        self::assertTrue($service->canTransition('pending', 'cancelled'));
        self::assertFalse($service->canTransition('pending', 'completed'));
    }

    public function testConfirmedCanBeCompletedOrCancelled(): void
    {
        $service = new AppointmentStatusService();

        self::assertTrue($service->canTransition('confirmed', 'completed'));
        self::assertTrue($service->canTransition('confirmed', 'cancelled'));
        self::assertFalse($service->canTransition('confirmed', 'pending'));
    }

    public function testTerminalStatusesCannotTransition(): void
    {
        $service = new AppointmentStatusService();

        self::assertSame([], $service->allowedTransitions('completed'));
        self::assertSame([], $service->allowedTransitions('cancelled'));
        self::assertFalse($service->canTransition('completed', 'confirmed'));
        self::assertFalse($service->canTransition('cancelled', 'pending'));
    }

    public function testUnknownStatusFailsClosed(): void
    {
        $service = new AppointmentStatusService();

        self::assertSame([], $service->allowedTransitions('mystery'));
        self::assertFalse($service->canTransition('mystery', 'confirmed'));
    }
}
