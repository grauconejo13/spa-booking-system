<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Controllers\ServicesController;
use SpaBooking\Models\Service;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\View\ViewRenderer;

final class ServicesControllerTest extends TestCase
{
    private ViewRenderer $views;

    protected function setUp(): void
    {
        $this->views = new ViewRenderer(dirname(__DIR__, 2) . '/app/Views');
    }

    public function testItRendersActiveServiceRecords(): void
    {
        $service = new Service(1, 'Forest Facial', 'forest-facial', 'A calming facial.', 50, 8650, true, 1);
        $repository = $this->repositoryReturning([$service]);

        $response = (new ServicesController($this->views, $repository))->index();

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Forest Facial', $response->body());
        self::assertStringContainsString('50 minutes', $response->body());
        self::assertStringContainsString('$86.50', $response->body());
    }

    public function testItRendersAFriendlyEmptyState(): void
    {
        $response = (new ServicesController($this->views, $this->repositoryReturning([])))->index();

        self::assertSame(200, $response->status());
        self::assertStringContainsString('New treatments are on the way', $response->body());
    }

    public function testItHandlesRepositoryFailuresWithoutExposingDetails(): void
    {
        $repository = new class implements ServiceCatalogRepository {
            public function findActive(): array
            {
                throw new RuntimeException('SQLSTATE password=secret internal-host');
            }
        };

        $response = (new ServicesController($this->views, $repository))->index();

        self::assertSame(503, $response->status());
        self::assertStringContainsString('Services are taking a short rest', $response->body());
        self::assertStringNotContainsString('SQLSTATE', $response->body());
        self::assertStringNotContainsString('secret', $response->body());
        self::assertStringNotContainsString('internal-host', $response->body());
    }

    /** @param list<Service> $services */
    private function repositoryReturning(array $services): ServiceCatalogRepository
    {
        return new class ($services) implements ServiceCatalogRepository {
            /** @param list<Service> $services */
            public function __construct(private readonly array $services)
            {
            }

            public function findActive(): array
            {
                return $this->services;
            }
        };
    }
}
