<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Validation;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SpaBooking\Models\AvailableSlot;
use SpaBooking\Validation\TimeSelectionValidator;

final class TimeSelectionValidatorTest extends TestCase
{
    public function testItAcceptsOnlyARecalculatedAvailableSlot(): void
    {
        $validator = new TimeSelectionValidator();
        $slot = new AvailableSlot(new DateTimeImmutable('2030-06-03 09:00'), [1, 2]);

        self::assertSame($slot, $validator->validate('09:00', [$slot])['slot']);
        self::assertNotNull($validator->validate('9:00', [$slot])['error']);
        self::assertNotNull($validator->validate('10:00', [$slot])['error']);
    }
}
