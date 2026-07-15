<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\View\ViewRenderer;

abstract class Controller
{
    public function __construct(protected readonly ViewRenderer $views)
    {
    }

    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = [], int $status = 200): Response
    {
        return new Response($this->views->render($view, $data), $status);
    }
}

