<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use SpaBooking\Controllers\BookingSubmissionController;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\BookingConflictException;
use SpaBooking\Services\BookingCreator;
use SpaBooking\Services\BookingSubmissionStore;
use SpaBooking\Validation\CustomerDetailsValidator;

final class BookingSubmissionControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $session = [];

    public function testValidSubmissionRedirectsAndRepeatedSubmissionIsIdempotent(): void
    {
        $counter = new \stdClass();
        $counter->calls = 0;
        $creator = new class ($counter) implements BookingCreator {
            public function __construct(private readonly \stdClass $counter)
            {
            }

            public function book(
                int $serviceId,
                string $preference,
                string $date,
                string $time,
                array $customer
            ): string {
                $this->counter->calls++;
                return 'SPA-7K4M9Q2X';
            }
        };
        [$controller, $input] = $this->fixture($creator);

        $first = $controller->confirm('7', $input);
        $second = $controller->confirm('7', $input);

        self::assertSame(303, $first->status());
        self::assertSame('/booking/confirmation/SPA-7K4M9Q2X', $first->headers()['Location']);
        self::assertSame(303, $second->status());
        self::assertSame(1, $counter->calls);
    }

    public function testInvalidCsrfAndCustomerValuesAreRejected(): void
    {
        $creator = $this->creator();
        [$controller, $input] = $this->fixture($creator);
        $input['_token'] = 'invalid';
        self::assertSame(422, $controller->confirm('7', $input)->status());

        [$controller, $input] = $this->fixture($creator);
        $input['email'] = 'not-an-email';
        self::assertSame(422, $controller->confirm('7', $input)->status());
    }

    public function testStaleSlotReturnsToDateTimeWithoutSensitiveUrlValues(): void
    {
        $creator = new class implements BookingCreator {
            public function book(
                int $serviceId,
                string $preference,
                string $date,
                string $time,
                array $customer
            ): string {
                throw new BookingConflictException();
            }
        };
        [$controller, $input] = $this->fixture($creator);
        $response = $controller->confirm('7', $input);
        $location = $response->headers()['Location'];

        self::assertSame(303, $response->status());
        self::assertStringContainsString('booking_error=stale', $location);
        self::assertStringNotContainsString('Avery', $location);
        self::assertStringNotContainsString('example.test', $location);
    }

    /** @return array{BookingSubmissionController, array<string, string>} */
    private function fixture(BookingCreator $creator): array
    {
        $csrf = new CsrfTokenManager($this->session);
        $submissions = new BookingSubmissionStore($this->session);
        $input = [
            '_token' => $csrf->token(),
            'submission_token' => $submissions->issue(),
            'therapist' => 'any',
            'date' => '2030-06-03',
            'time' => '09:00',
            'name' => 'Avery Reed',
            'email' => 'avery@example.test',
            'phone' => '555-0102',
            'notes' => '',
        ];

        return [new BookingSubmissionController(
            $csrf,
            new CustomerDetailsValidator(),
            $creator,
            $submissions
        ), $input];
    }

    private function creator(): BookingCreator
    {
        return new class implements BookingCreator {
            public function book(
                int $serviceId,
                string $preference,
                string $date,
                string $time,
                array $customer
            ): string {
                return 'SPA-7K4M9Q2X';
            }
        };
    }
}
