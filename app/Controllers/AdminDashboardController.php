<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Security\AdminSession;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\View\ViewRenderer;

final class AdminDashboardController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly AdminSession $session,
        private readonly CsrfTokenManager $csrf
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin/dashboard', [
            'title' => 'Admin dashboard',
            'adminName' => $this->session->adminName(),
            'csrfToken' => $this->csrf->token(),
        ]));
    }
}
