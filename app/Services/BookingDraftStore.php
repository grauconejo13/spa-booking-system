<?php

declare(strict_types=1);

namespace SpaBooking\Services;

final class BookingDraftStore
{
    private const string SESSION_KEY = 'booking_customer_drafts';

    /** @var array<string, mixed> */
    private array $session;

    /** @param array<string, mixed> $session */
    public function __construct(array &$session)
    {
        $this->session =& $session;
    }

    /** @return array{name: string, email: string, phone: string, notes: string} */
    public function get(int $serviceId): array
    {
        $drafts = $this->session[self::SESSION_KEY] ?? null;
        $draft = is_array($drafts) ? ($drafts[$serviceId] ?? null) : null;

        return is_array($draft)
            ? [
                'name' => is_string($draft['name'] ?? null) ? $draft['name'] : '',
                'email' => is_string($draft['email'] ?? null) ? $draft['email'] : '',
                'phone' => is_string($draft['phone'] ?? null) ? $draft['phone'] : '',
                'notes' => is_string($draft['notes'] ?? null) ? $draft['notes'] : '',
            ]
            : ['name' => '', 'email' => '', 'phone' => '', 'notes' => ''];
    }

    /** @param array{name: string, email: string, phone: string, notes: string} $values */
    public function put(int $serviceId, array $values): void
    {
        $drafts = $this->session[self::SESSION_KEY] ?? [];
        $drafts = is_array($drafts) ? $drafts : [];
        $drafts[$serviceId] = $values;
        $this->session[self::SESSION_KEY] = $drafts;
    }
}
