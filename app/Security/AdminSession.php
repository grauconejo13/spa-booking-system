<?php

declare(strict_types=1);

namespace SpaBooking\Security;

final class AdminSession
{
    private const string ADMIN_ID = 'admin_user_id';
    private const string ADMIN_NAME = 'admin_user_name';
    private const string LAST_ACTIVITY = 'admin_last_activity';
    private const int IDLE_TIMEOUT_SECONDS = 1800;

    /** @var array<string, mixed> */
    private array $session;

    /** @param array<string, mixed> $session */
    public function __construct(array &$session)
    {
        $this->session =& $session;
    }

    public function signIn(int $adminId, string $name, int $now): void
    {
        session_regenerate_id(true);
        $this->session[self::ADMIN_ID] = $adminId;
        $this->session[self::ADMIN_NAME] = $name;
        $this->session[self::LAST_ACTIVITY] = $now;
    }

    public function isAuthenticated(int $now): bool
    {
        $adminId = $this->session[self::ADMIN_ID] ?? null;
        $lastActivity = $this->session[self::LAST_ACTIVITY] ?? null;

        if (!is_int($adminId) || !is_int($lastActivity)) {
            return false;
        }

        if ($now - $lastActivity > self::IDLE_TIMEOUT_SECONDS) {
            $this->clear();
            return false;
        }

        $this->session[self::LAST_ACTIVITY] = $now;
        return true;
    }

    public function adminName(): ?string
    {
        $name = $this->session[self::ADMIN_NAME] ?? null;

        return is_string($name) ? $name : null;
    }

    public function signOut(): void
    {
        $this->clear();
        session_regenerate_id(true);
    }

    private function clear(): void
    {
        unset(
            $this->session[self::ADMIN_ID],
            $this->session[self::ADMIN_NAME],
            $this->session[self::LAST_ACTIVITY]
        );
    }
}
