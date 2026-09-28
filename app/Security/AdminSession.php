<?php

declare(strict_types=1);

namespace SpaBooking\Security;

final class AdminSession
{
    private const SESSION_KEY = 'admin_auth';
    private const TIMEOUT_SECONDS = 1800;

    /** @param array<string, mixed> $session */
    public function __construct(private array &$session)
    {
    }

    /** @return array{id: int, name: string, email: string}|null */
    public function current(): ?array
    {
        $auth = $this->session[self::SESSION_KEY] ?? null;

        if (!is_array($auth)) {
            return null;
        }

        $lastSeenAt = (int) ($auth['last_seen_at'] ?? 0);
        if ($lastSeenAt < time() - self::TIMEOUT_SECONDS) {
            $this->logout();
            return null;
        }

        $this->session[self::SESSION_KEY]['last_seen_at'] = time();

        return [
            'id' => (int) ($auth['id'] ?? 0),
            'name' => (string) ($auth['name'] ?? ''),
            'email' => (string) ($auth['email'] ?? ''),
        ];
    }

    public function login(int $id, string $name, string $email): void
    {
        session_regenerate_id(true);
        $this->session[self::SESSION_KEY] = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'last_seen_at' => time(),
        ];
    }

    public function logout(): void
    {
        unset($this->session[self::SESSION_KEY]);
        session_regenerate_id(true);
    }
}
