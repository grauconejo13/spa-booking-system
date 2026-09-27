<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Repositories\AdminAppointmentRepository;
use SpaBooking\Security\AdminSession;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\AdminAuthService;
use SpaBooking\View\ViewRenderer;

final class AdminController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly AdminAuthService $auth,
        private readonly AdminSession $session,
        private readonly CsrfTokenManager $csrf,
        private readonly AdminAppointmentRepository $appointments
    ) {
    }

    public function loginForm(): Response
    {
        if ($this->session->current() !== null) {
            return $this->redirect('/admin');
        }

        return new Response($this->views->render('admin/login', [
            'title' => 'Admin sign in',
            'csrfToken' => $this->csrf->token(),
            'error' => null,
        ]));
    }

    /** @param array<string, mixed> $input */
    public function login(array $input): Response
    {
        if (!$this->csrf->validate($input['_token'] ?? null)) {
            return new Response('Invalid request.', 419, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '' || !$this->auth->attempt($email, $password)) {
            return new Response($this->views->render('admin/login', [
                'title' => 'Admin sign in',
                'csrfToken' => $this->csrf->token(),
                'error' => 'The email or password is incorrect.',
                'email' => $email,
            ]), 422);
        }

        return $this->redirect('/admin');
    }

    public function dashboard(): Response
    {
        $admin = $this->session->current();
        if ($admin === null) {
            return $this->redirect('/admin/login');
        }

        return new Response($this->views->render('admin/dashboard', [
            'title' => 'Admin dashboard',
            'admin' => $admin,
            'appointments' => $this->appointments->upcoming(),
            'csrfToken' => $this->csrf->token(),
        ]));
    }

    /** @param array<string, mixed> $input */
    public function logout(array $input): Response
    {
        if ($this->session->current() === null) {
            return $this->redirect('/admin/login');
        }

        if (!$this->csrf->validate($input['_token'] ?? null)) {
            return new Response('Invalid request.', 419, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $this->session->logout();
        return $this->redirect('/admin/login');
    }

    private function redirect(string $location): Response
    {
        return new Response('', 303, ['Location' => $location]);
    }
}
