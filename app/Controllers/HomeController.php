<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Services\InMemoryServiceCatalog;
use SpaBooking\View\ViewRenderer;

final class HomeController extends Controller
{
    public function __construct(
        ViewRenderer $views,
        private readonly InMemoryServiceCatalog $services
    ) {
        parent::__construct($views);
    }

    public function index(): Response
    {
        return $this->render('home', [
            'title' => 'Quiet care, thoughtfully planned',
            'featuredServices' => array_slice($this->services->all(), 0, 2),
        ]);
    }

    public function services(): Response
    {
        return $this->render('services', [
            'title' => 'Spa services',
            'services' => $this->services->all(),
        ]);
    }
}

