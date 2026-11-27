<?php

declare(strict_types=1);

namespace SpaBooking\Security;

final class AdminSession
{
    private const SESSION_KEY = 'admin_auth';
    private const IDLE_TIMEOUT_SECONDS = 1800;
    private const ABSOLUTE_TIMEOUT_SECONDS = 28800;
    private const LOGIN_ATTEMPTS_KEY = 'admin_login_attempts';
    private const MAX_LOGIN_FAILURES = 5;
    private const LOGIN_WINDOW_SECONDS = 600;
    private const LOGIN_BACKOFF_SECONDS = 300;

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

    public function loginRetryAfterSeconds(): int
    {
        $state = $this->session[self::LOGIN_ATTEMPTS_KEY] ?? null;
        if (!is_array($state)) {
            return 0;
        }

        $failures = $state['failures'] ?? null;
        $startedAt = $state['started_at'] ?? null;
        $lastFailedAt = $state['last_failed_at'] ?? null;
        if (!is_int($failures) || !is_int($startedAt) || !is_int($lastFailedAt)) {
            $this->clearLoginFailures();
            return 0;
        }

        $now = time();
        if ($startedAt < $now - self::LOGIN_WINDOW_SECONDS) {
            $this->clearLoginFailures();
            return 0;
        }

        if ($failures < self::MAX_LOGIN_FAILURES) {
            return 0;
        }

        return max(0, ($lastFailedAt + self::LOGIN_BACKOFF_SECONDS) - $now);
    }

    public function recordLoginFailure(): void
    {
        $now = time();
        $state = $this->session[self::LOGIN_ATTEMPTS_KEY] ?? null;

        if (
            !is_array($state)
            || !is_int($state['started_at'] ?? null)
            || $state['started_at'] < $now - self::LOGIN_WINDOW_SECONDS
        ) {
            $state = [
                'failures' => 0,
                'started_at' => $now,
                'last_failed_at' => $now,
            ];
        }

        $state['failures'] = (int) ($state['failures'] ?? 0) + 1;
        $state['last_failed_at'] = $now;
        $this->session[self::LOGIN_ATTEMPTS_KEY] = $state;
    }

    public function clearLoginFailures(): void
    {
        unset($this->session[self::LOGIN_ATTEMPTS_KEY]);
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
