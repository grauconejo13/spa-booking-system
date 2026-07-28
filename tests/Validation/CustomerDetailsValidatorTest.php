<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Validation;

use PHPUnit\Framework\TestCase;
use SpaBooking\Validation\CustomerDetailsValidator;

final class CustomerDetailsValidatorTest extends TestCase
{
    private CustomerDetailsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CustomerDetailsValidator();
    }

    public function testItTrimsAndAcceptsValidCustomerDetails(): void
    {
        $result = $this->validator->validate([
            'name' => '  Avery Reed  ',
            'email' => '  avery@example.test ',
            'phone' => '  555-0102 ',
            'notes' => '  Quiet room, please.  ',
        ]);

        self::assertSame([], $result['errors']);
        self::assertSame('Avery Reed', $result['values']['name']);
        self::assertSame('avery@example.test', $result['values']['email']);
        self::assertSame('555-0102', $result['values']['phone']);
        self::assertSame('Quiet room, please.', $result['values']['notes']);
    }

    public function testItValidatesRequiredNameEmailAndPhone(): void
    {
        $result = $this->validator->validate(['name' => '', 'email' => 'invalid', 'phone' => '12']);

        self::assertArrayHasKey('name', $result['errors']);
        self::assertArrayHasKey('email', $result['errors']);
        self::assertArrayHasKey('phone', $result['errors']);
    }

    public function testItLimitsOptionalNotes(): void
    {
        $result = $this->validator->validate([
            'name' => 'Avery Reed',
            'email' => 'avery@example.test',
            'phone' => '555-0102',
            'notes' => str_repeat('x', 1001),
        ]);

        self::assertArrayHasKey('notes', $result['errors']);
    }
}
