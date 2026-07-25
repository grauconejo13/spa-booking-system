<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\View\ViewRenderer;
use Throwable;

final class ServicesController extends Controller
{
    public function __construct(
        ViewRenderer $views,
        private readonly ServiceCatalogRepository $services
    ) {
        parent::__construct($views);
    }

    public function index(): Response
    {
        try {
            $services = $this->services->findActive();
        } catch (Throwable) {
            return $this->render('services', [
                'title' => 'Spa services',
                'services' => [],
                'catalogError' => true,
            ], 503);
        }

        return $this->render('services', [
            'title' => 'Spa services',
            'services' => $services,
            'catalogError' => false,
        ]);
    }
}
