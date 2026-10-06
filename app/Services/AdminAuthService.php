<?php

declare(strict_types=1);

namespace SpaBooking\Services;

use SpaBooking\Repositories\AdminUserRepository;
use SpaBooking\Security\AdminSession;

final class AdminAuthService
{
    public function __construct(
        private readonly AdminUserRepository $admins,
        private readonly AdminSession $session
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        $admin = $this->admins->findActiveByEmail($email);

        if ($admin === null || !password_verify($password, $admin->passwordHash)) {
            return false;
        }

        $this->session->login($admin->id, $admin->name, $admin->email);
        $this->admins->markLogin($admin->id);

        return true;
    }
}
