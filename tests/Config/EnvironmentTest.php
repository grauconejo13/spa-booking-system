<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Config;

use PHPUnit\Framework\TestCase;
use SpaBooking\Config\Environment;

final class EnvironmentTest extends TestCase
{
    private const KEY = 'SPA_BOOKING_TEST_VALUE';

    protected function tearDown(): void
    {
        putenv(self::KEY);
        unset($_ENV[self::KEY], $_SERVER[self::KEY]);
    }

    public function testItLoadsAQuotedValueFromAFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'spa-env-');
        self::assertIsString($path);
        file_put_contents($path, self::KEY . '="fictional value"');

        try {
            Environment::load($path);
            self::assertSame('fictional value', Environment::get(self::KEY));
        } finally {
            unlink($path);
        }
    }

    public function testExistingEnvironmentValuesAreNotOverwritten(): void
    {
        putenv(self::KEY . '=external');
        $path = tempnam(sys_get_temp_dir(), 'spa-env-');
        self::assertIsString($path);
        file_put_contents($path, self::KEY . '=file');

        try {
            Environment::load($path);
            self::assertSame('external', Environment::get(self::KEY));
        } finally {
            unlink($path);
        }
    }
}
