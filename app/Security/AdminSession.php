<?php

declare(strict_types=1);

namespace SpaBooking\Security;

final class AdminSession
{
    private const SESSION_KEY = 'admin_auth';
    private const IDLE_TIMEOUT_SECONDS = 1800;
    private const ABSOLUTE_TIMEOUT_SECONDS = 28800;

    /** @param array<string, mixed> $session */
    public function __construct(private array &$session)
    {
    }

    /** @return array{id: int, name: string, email: string}|null */
    public function current(): ?array
    {
        $auth = $this->session[self::SESSION_KEY] ?? null;

        if (!$this->isValidAuthPayload($auth)) {
            $this->clear();
            return null;
        }

        $now = time();
        if (
            $auth['last_seen_at'] < $now - self::IDLE_TIMEOUT_SECONDS
            || $auth['authenticated_at'] < $now - self::ABSOLUTE_TIMEOUT_SECONDS
        ) {
            $this->clear();
            return null;
        }

        $this->session[self::SESSION_KEY]['last_seen_at'] = $now;

        return [
            'id' => $auth['id'],
            'name' => $auth['name'],
            'email' => $auth['email'],
        ];
    }

    public function login(int $id, string $name, string $email): void
    {
        session_regenerate_id(true);
        $now = time();

        $this->session[self::SESSION_KEY] = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'last_seen_at' => $now,
            'authenticated_at' => $now,
        ];
    }

    public function logout(): void
    {
        $this->clear();
        session_regenerate_id(true);
    }

    private function clear(): void
    {
        unset($this->session[self::SESSION_KEY]);
    }

    private function isValidAuthPayload(mixed $auth): bool
    {
        return is_array($auth)
            && is_int($auth['id'] ?? null)
            && $auth['id'] > 0
            && is_string($auth['name'] ?? null)
            && $auth['name'] !== ''
            && is_string($auth['email'] ?? null)
            && $auth['email'] !== ''
            && is_int($auth['last_seen_at'] ?? null)
            && is_int($auth['authenticated_at'] ?? null);
    }
}
