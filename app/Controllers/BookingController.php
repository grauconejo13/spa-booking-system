<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\View\ViewRenderer;
use Throwable;

final class BookingController extends Controller
{
    public function __construct(
        ViewRenderer $views,
        private readonly ServiceCatalogRepository $services,
        private readonly TherapistCatalogRepository $therapists
    ) {
        parent::__construct($views);
    }

    public function start(string $serviceId): Response
    {
        $id = filter_var($serviceId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!is_int($id)) {
            return $this->notFound();
        }

        try {
            $service = $this->services->findActiveById($id);

            if ($service === null) {
                return $this->notFound();
            }

            $therapists = $this->therapists->findActiveQualifiedForService($id);
        } catch (Throwable) {
            return $this->render('booking-entry', [
                'title' => 'Booking unavailable',
                'service' => null,
                'therapists' => [],
                'bookingError' => true,
            ], 503);
        }

        return $this->render('booking-entry', [
            'title' => 'Start booking: ' . $service->name,
            'service' => $service,
            'therapists' => $therapists,
            'bookingError' => false,
        ]);
    }

    private function notFound(): Response
    {
        return $this->render('errors/404', ['title' => 'Page not found'], 404);
    }
}
