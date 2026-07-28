<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Services;

use PHPUnit\Framework\TestCase;
use SpaBooking\Services\TherapistAssignmentService;

final class TherapistAssignmentServiceTest extends TestCase
{
    public function testItChoosesTheLowestWorkloadThenLowestId(): void
    {
        $service = new TherapistAssignmentService();

        self::assertSame(7, $service->assign([3, 7, 9], [3 => 2, 7 => 1, 9 => 3]));
        self::assertSame(3, $service->assign([9, 7, 3], [3 => 1, 7 => 1, 9 => 1]));
        self::assertNull($service->assign([], []));
    }
}
