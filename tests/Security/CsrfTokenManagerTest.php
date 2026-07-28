<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Security;

use PHPUnit\Framework\TestCase;
use SpaBooking\Security\CsrfTokenManager;

final class CsrfTokenManagerTest extends TestCase
{
    public function testItStoresAndValidatesASessionToken(): void
    {
        $session = [];
        $tokens = new CsrfTokenManager($session);
        $token = $tokens->token();

        self::assertSame(64, strlen($token));
        self::assertTrue($tokens->validate($token));
        self::assertFalse($tokens->validate('invalid'));
        self::assertFalse($tokens->validate(null));
    }
}
