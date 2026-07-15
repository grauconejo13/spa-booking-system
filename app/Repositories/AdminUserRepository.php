<?php

declare(strict_types=1);

namespace SpaBooking\Repositories;

use PDO;
use SpaBooking\Models\AdminUser;

final class AdminUserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findActiveByEmail(string $email): ?AdminUser
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, is_active
             FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1'
        );
        assert($statement !== false);
        $statement->execute(['email' => strtolower(trim($email))]);
        $row = $statement->fetch();

        return is_array($row) ? new AdminUser(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (bool) $row['is_active']
        ) : null;
    }
}
