<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use DateTimeZone;
use SpaBooking\Http\Response;
use SpaBooking\Repositories\AppointmentBookingRepository;
use SpaBooking\View\ViewRenderer;
use Throwable;

final class BookingConfirmationController extends Controller
{
    public function __construct(
        ViewRenderer $views,
        private readonly AppointmentBookingRepository $appointments,
        private readonly DateTimeZone $timezone
    ) {
        parent::__construct($views);
    }

    public function show(string $reference): Response
    {
        if (preg_match('/^SPA-[23456789A-HJ-NP-Z]{8}$/', $reference) !== 1) {
            return $this->notFound();
        }

        try {
            $appointment = $this->appointments->findByReference($reference);
            if ($appointment === null) {
                return $this->notFound();
            }

            return $this->render('booking-confirmation', [
                'title' => 'Appointment request received',
                'appointment' => $appointment,
                'localStart' => $appointment->startsAt->setTimezone($this->timezone),
            ]);
        } catch (Throwable) {
            return $this->render('errors/500', ['title' => 'Something went wrong'], 503);
        }
    }

    private function notFound(): Response
    {
        return $this->render('errors/404', ['title' => 'Page not found'], 404);
    }
}
