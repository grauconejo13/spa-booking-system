<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Controllers\BookingController;
use SpaBooking\Models\Service;
use SpaBooking\Models\Therapist;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\View\ViewRenderer;

final class BookingControllerTest extends TestCase
{
    private ViewRenderer $views;

    protected function setUp(): void
    {
        $this->views = new ViewRenderer(dirname(__DIR__, 2) . '/app/Views');
    }

    public function testItRendersTheBookingEntryAndSelectedServiceSummary(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Forest Facial', $response->body());
        self::assertStringContainsString('A calming facial.', $response->body());
        self::assertStringContainsString('50 minutes', $response->body());
        self::assertStringContainsString('$86.50', $response->body());
        self::assertStringContainsString('Back to service details', $response->body());
        self::assertStringContainsString('Date selection and available appointment times', $response->body());
        self::assertStringContainsString('disabled>Continue', $response->body());
    }

    public function testItOffersAnyAndSpecificQualifiedTherapistChoices(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7');

        self::assertStringContainsString('Any available therapist', $response->body());
        self::assertStringContainsString('Mara Vale', $response->body());
        self::assertStringContainsString('Restorative facial specialist.', $response->body());
        self::assertStringContainsString('value="3"', $response->body());
    }

    public function testItShowsAFriendlyStateWithoutQualifiedTherapists(): void
    {
        $response = $this->controller($this->service(), [])->start('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('No therapists are currently assigned', $response->body());
    }

    public function testItReturnsNotFoundForAnInvalidServiceId(): void
    {
        $response = $this->controller($this->service(), [])->start('invalid');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('That page is resting', $response->body());
    }

    public function testItReturnsNotFoundForAMissingOrInactiveService(): void
    {
        $response = $this->controller(null, [])->start('7');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('That page is resting', $response->body());
    }

    public function testItHandlesRepositoryFailuresWithoutExposingDetails(): void
    {
        $services = new class implements ServiceCatalogRepository {
            public function findActive(): array
            {
                return [];
            }

            public function findActiveById(int $id): ?Service
            {
                throw new RuntimeException('SQLSTATE password=secret internal-host');
            }
        };

        $response = (new BookingController(
            $this->views,
            $services,
            $this->therapistRepository([])
        ))->start('7');

        self::assertSame(503, $response->status());
        self::assertStringContainsString('Booking is taking a short rest', $response->body());
        self::assertStringNotContainsString('SQLSTATE', $response->body());
        self::assertStringNotContainsString('secret', $response->body());
        self::assertStringNotContainsString('internal-host', $response->body());
    }

    /** @param list<Therapist> $therapists */
    private function controller(?Service $service, array $therapists): BookingController
    {
        $services = new class ($service) implements ServiceCatalogRepository {
            public function __construct(private readonly ?Service $service)
            {
            }

            public function findActive(): array
            {
                return $this->service === null ? [] : [$this->service];
            }

            public function findActiveById(int $id): ?Service
            {
                return $this->service?->id === $id && $this->service->isActive ? $this->service : null;
            }
        };

        return new BookingController($this->views, $services, $this->therapistRepository($therapists));
    }

    /** @param list<Therapist> $therapists */
    private function therapistRepository(array $therapists): TherapistCatalogRepository
    {
        return new class ($therapists) implements TherapistCatalogRepository {
            /** @param list<Therapist> $therapists */
            public function __construct(private readonly array $therapists)
            {
            }

            public function findActiveQualifiedForService(int $serviceId): array
            {
                return $this->therapists;
            }
        };
    }

    private function service(): Service
    {
        return new Service(7, 'Forest Facial', 'forest-facial', 'A calming facial.', 50, 8650, true, 1);
    }

    private function therapist(): Therapist
    {
        return new Therapist(3, 'Mara Vale', 'mara-vale', 'Restorative facial specialist.', true, 1);
    }
}
