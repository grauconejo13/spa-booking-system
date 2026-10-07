<?php

declare(strict_types=1);

namespace SpaBooking\Services;

use SpaBooking\Models\AdminUser;
use SpaBooking\Repositories\AdminUserRepository;

final class AdminAuthService
{
    public function __construct(private readonly AdminUserRepository $admins)
    {
    }

    public function authenticate(string $email, string $password): ?AdminUser
    {
        $admin = $this->admins->findActiveByEmail($email);

        if ($admin === null || !password_verify($password, $admin->passwordHash)) {
            return null;
        }

        return $admin;
    }
}
