<?php

declare(strict_types=1);

namespace SpaBooking\Tests\View;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Models\Service;
use SpaBooking\View\ViewRenderer;

final class ViewRendererTest extends TestCase
{
    private ViewRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ViewRenderer(dirname(__DIR__, 2) . '/app/Views');
    }

    public function testItRendersContentInsideTheSharedLayoutAndEscapesTheTitle(): void
    {
        $html = $this->renderer->render('errors/404', ['title' => '<script>alert(1)</script>']);

        self::assertStringContainsString('That page is resting', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<title><script>', $html);
    }

    public function testItRejectsViewTraversal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->renderer->render('../outside');
    }

    public function testServicesViewEscapesDatabaseValuesAndFormatsDetails(): void
    {
        $service = new Service(
            1,
            '<script>Forest Facial</script>',
            'forest-facial',
            '<img src=x onerror=alert(1)>',
            50,
            8650,
            true,
            1
        );

        $html = $this->renderer->render('services', [
            'title' => 'Spa services',
            'services' => [$service],
            'catalogError' => false,
        ]);

        self::assertStringContainsString('&lt;script&gt;Forest Facial&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        self::assertStringNotContainsString('<script>Forest Facial</script>', $html);
        self::assertStringContainsString('50 minutes', $html);
        self::assertStringContainsString('$86.50', $html);
    }
}
