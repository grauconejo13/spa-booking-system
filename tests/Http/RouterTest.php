<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Http\Response;
use SpaBooking\Http\Router;

final class RouterTest extends TestCase
{
    public function testItDispatchesARegisteredRouteAndIgnoresTheQueryString(): void
    {
        $router = new Router();
        $router->get('/services', static fn (): Response => new Response('services'));

        $response = $router->dispatch('GET', '/services?featured=1');

        self::assertSame(200, $response->status());
        self::assertSame('services', $response->body());
    }

    public function testItNormalizesTrailingSlashes(): void
    {
        $router = new Router();
        $router->get('/services', static fn (): Response => new Response('services'));

        self::assertSame('services', $router->dispatch('GET', '/services/')->body());
    }

    public function testItMatchesAServiceIdRouteAndPassesTheParameter(): void
    {
        $router = new Router();
        $router->get('/services/{id}', static fn (string $id): Response => new Response('service-' . $id));

        $response = $router->dispatch('GET', '/services/42');

        self::assertSame(200, $response->status());
        self::assertSame('service-42', $response->body());
    }

    public function testItMatchesABookingServiceIdRouteAndPassesTheParameter(): void
    {
        $router = new Router();
        $router->get('/book/{serviceId}', static fn (string $id): Response => new Response('book-' . $id));

        $response = $router->dispatch('GET', '/book/42');

        self::assertSame(200, $response->status());
        self::assertSame('book-42', $response->body());
    }

    public function testItMatchesAHttpPostBookingRoute(): void
    {
        $router = new Router();
        $router->post('/book/{serviceId}', static fn (string $id): Response => new Response('review-' . $id));

        self::assertSame('review-42', $router->dispatch('POST', '/book/42')->body());
    }

    public function testUnknownPathsAndMethodsUseTheNotFoundResponse(): void
    {
        $router = new Router(static fn (): Response => new Response('missing', 404));
        $router->get('/services', static fn (): Response => new Response('services'));

        self::assertSame(404, $router->dispatch('GET', '/missing')->status());
        self::assertSame(404, $router->dispatch('POST', '/services')->status());
    }

    public function testDuplicateRoutesAreRejected(): void
    {
        $router = new Router();
        $router->get('/', static fn (): Response => new Response('first'));

        $this->expectException(RuntimeException::class);
        $router->get('/', static fn (): Response => new Response('second'));
    }
}
