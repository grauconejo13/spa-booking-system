<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\View\ViewRenderer;
use Throwable;

final class ServicesController extends Controller
{
    public function __construct(
        ViewRenderer $views,
        private readonly ServiceCatalogRepository $services,
        private readonly ?TherapistCatalogRepository $therapists = null
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

    public function show(string $id): Response
    {
        $serviceId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!is_int($serviceId)) {
            return $this->notFound();
        }

        try {
            $service = $this->services->findActiveById($serviceId);

            if ($service === null) {
                return $this->notFound();
            }

            $therapists = $this->therapists?->findActiveQualifiedForService($serviceId) ?? [];
        } catch (Throwable) {
            return $this->render('service-detail', [
                'title' => 'Service unavailable',
                'service' => null,
                'therapists' => [],
                'detailError' => true,
            ], 503);
        }

        return $this->render('service-detail', [
            'title' => $service->name,
            'service' => $service,
            'therapists' => $therapists,
            'detailError' => false,
        ]);
    }

    private function notFound(): Response
    {
        return $this->render('errors/404', ['title' => 'Page not found'], 404);
    }
}
