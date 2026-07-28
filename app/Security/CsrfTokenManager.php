<?php

declare(strict_types=1);

namespace SpaBooking\Security;

final class CsrfTokenManager
{
    private const string SESSION_KEY = 'booking_csrf_token';

    /** @var array<string, mixed> */
    private array $session;

    /** @param array<string, mixed> $session */
    public function __construct(array &$session)
    {
        $this->session =& $session;
    }

    public function token(): string
    {
        $token = $this->session[self::SESSION_KEY] ?? null;

        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public function validate(mixed $submittedToken): bool
    {
        $storedToken = $this->session[self::SESSION_KEY] ?? null;

        return is_string($storedToken)
            && is_string($submittedToken)
            && hash_equals($storedToken, $submittedToken);
    }
}
