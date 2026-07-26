<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Controllers\ServicesController;
use SpaBooking\Models\Service;
use SpaBooking\Models\Therapist;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\View\ViewRenderer;

final class ServiceDetailControllerTest extends TestCase
{
    private ViewRenderer $views;

    protected function setUp(): void
    {
        $this->views = new ViewRenderer(dirname(__DIR__, 2) . '/app/Views');
    }

    public function testItRendersAServiceDetailResponse(): void
    {
        $response = $this->controller($this->service(), [])->show('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Forest Facial', $response->body());
        self::assertStringContainsString('50 minutes', $response->body());
        self::assertStringContainsString('$86.50', $response->body());
        self::assertStringContainsString('Back to services', $response->body());
        self::assertStringContainsString('Booking coming soon', $response->body());
    }

    public function testItReturnsNotFoundForAMissingOrInactiveService(): void
    {
        $response = $this->controller(null, [])->show('7');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('That page is resting', $response->body());
    }

    public function testItReturnsNotFoundForAnInvalidServiceId(): void
    {
        $response = $this->controller($this->service(), [])->show('not-a-number');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('That page is resting', $response->body());
    }

    public function testItRendersQualifiedTherapistsAndBiographies(): void
    {
        $therapists = [new Therapist(3, 'Mara Vale', 'mara-vale', 'Restorative facial specialist.', true, 1)];

        $response = $this->controller($this->service(), $therapists)->show('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Mara Vale', $response->body());
        self::assertStringContainsString('Restorative facial specialist.', $response->body());
    }

    public function testItRendersAFriendlyMessageWithoutQualifiedTherapists(): void
    {
        $response = $this->controller($this->service(), [])->show('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Therapist availability is being refreshed', $response->body());
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
        $therapists = $this->therapistRepository([]);

        $response = (new ServicesController($this->views, $services, $therapists))->show('7');

        self::assertSame(503, $response->status());
        self::assertStringContainsString('taking a short rest', $response->body());
        self::assertStringNotContainsString('SQLSTATE', $response->body());
        self::assertStringNotContainsString('secret', $response->body());
        self::assertStringNotContainsString('internal-host', $response->body());
    }

    /** @param list<Therapist> $therapists */
    private function controller(?Service $service, array $therapists): ServicesController
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

        return new ServicesController($this->views, $services, $this->therapistRepository($therapists));
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
}
