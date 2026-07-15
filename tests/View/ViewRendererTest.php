<?php

declare(strict_types=1);

namespace SpaBooking\Tests\View;

use PHPUnit\Framework\TestCase;
use RuntimeException;
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
}

