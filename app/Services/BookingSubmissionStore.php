<?php

declare(strict_types=1);

namespace SpaBooking\Services;

final class BookingSubmissionStore
{
    private const string SESSION_KEY = 'booking_submissions';

    /** @var array<string, mixed> */
    private array $session;

    /** @param array<string, mixed> $session */
    public function __construct(array &$session)
    {
        $this->session =& $session;
    }

    public function issue(): string
    {
        $token = bin2hex(random_bytes(24));
        $this->session[self::SESSION_KEY][$token] = null;

        return $token;
    }

    public function reference(string $token): ?string
    {
        $value = $this->session[self::SESSION_KEY][$token] ?? null;

        return is_string($value) ? $value : null;
    }

    public function isIssued(string $token): bool
    {
        return isset($this->session[self::SESSION_KEY])
            && is_array($this->session[self::SESSION_KEY])
            && array_key_exists($token, $this->session[self::SESSION_KEY]);
    }

    public function complete(string $token, string $reference): void
    {
        $this->session[self::SESSION_KEY][$token] = $reference;
    }
}
