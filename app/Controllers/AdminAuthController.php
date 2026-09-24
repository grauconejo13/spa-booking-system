<?php

declare(strict_types=1);

namespace SpaBooking\Controllers;

use SpaBooking\Http\Response;
use SpaBooking\Security\AdminSession;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\AdminAuthService;
use SpaBooking\View\ViewRenderer;

final class AdminAuthController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly AdminAuthService $auth,
        private readonly AdminSession $session,
        private readonly CsrfTokenManager $csrf
    ) {
    }

    public function loginForm(): Response
    {
        return new Response($this->views->render('admin/login', [
            'title' => 'Admin sign in',
            'csrfToken' => $this->csrf->token(),
            'error' => null,
            'email' => '',
        ]));
    }

    /** @param array<string, mixed> $input */
    public function login(array $input): Response
    {
        if (!$this->csrf->validate($input['csrf_token'] ?? null)) {
            return $this->loginError('', 'Your session expired. Please try again.', 419);
        }

        $email = is_string($input['email'] ?? null) ? trim($input['email']) : '';
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $admin = $this->auth->authenticate($email, $password);

        if ($admin === null) {
            return $this->loginError($email, 'The email or password is incorrect.', 422);
        }

        $this->session->signIn($admin->id, $admin->name, time());

        return new Response('', 303, ['Location' => '/admin']);
    }

    /** @param array<string, mixed> $input */
    public function logout(array $input): Response
    {
        if (!$this->csrf->validate($input['csrf_token'] ?? null)) {
            return new Response('Invalid request.', 419, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $this->session->signOut();

        return new Response('', 303, ['Location' => '/admin/login']);
    }

    private function loginError(string $email, string $message, int $status): Response
    {
        return new Response($this->views->render('admin/login', [
            'title' => 'Admin sign in',
            'csrfToken' => $this->csrf->token(),
            'error' => $message,
            'email' => $email,
        ]), $status);
    }
}
