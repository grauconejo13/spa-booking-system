<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Security;

use PHPUnit\Framework\TestCase;
use SpaBooking\Security\AdminSession;

final class AdminSessionTest extends TestCase
{
    public function testCurrentReturnsAuthenticatedAdminAndRefreshesActivity(): void
    {
        $now = time();
        $session = [
            'admin_auth' => [
                'id' => 7,
                'name' => 'Demo Admin',
                'email' => 'admin@example.test',
                'last_seen_at' => $now - 60,
                'authenticated_at' => $now - 300,
            ],
        ];

        $admin = (new AdminSession($session))->current();

        self::assertSame([
            'id' => 7,
            'name' => 'Demo Admin',
            'email' => 'admin@example.test',
        ], $admin);
        self::assertGreaterThanOrEqual($now, $session['admin_auth']['last_seen_at']);
    }

    public function testCurrentRejectsMalformedSessionState(): void
    {
        $session = [
            'admin_auth' => [
                'id' => '7',
                'name' => 'Demo Admin',
                'email' => 'admin@example.test',
                'last_seen_at' => time(),
                'authenticated_at' => time(),
            ],
        ];

        $admin = (new AdminSession($session))->current();

        self::assertNull($admin);
        self::assertArrayNotHasKey('admin_auth', $session);
    }

    public function testCurrentClearsIdleExpiredSession(): void
    {
        $now = time();
        $session = [
            'admin_auth' => [
                'id' => 7,
                'name' => 'Demo Admin',
                'email' => 'admin@example.test',
                'last_seen_at' => $now - 1801,
                'authenticated_at' => $now - 3600,
            ],
        ];

        $admin = (new AdminSession($session))->current();

        self::assertNull($admin);
        self::assertArrayNotHasKey('admin_auth', $session);
    }

    public function testCurrentClearsSessionPastAbsoluteLifetime(): void
    {
        $now = time();
        $session = [
            'admin_auth' => [
                'id' => 7,
                'name' => 'Demo Admin',
                'email' => 'admin@example.test',
                'last_seen_at' => $now - 60,
                'authenticated_at' => $now - 28801,
            ],
        ];

        $admin = (new AdminSession($session))->current();

        self::assertNull($admin);
        self::assertArrayNotHasKey('admin_auth', $session);
    }
}
