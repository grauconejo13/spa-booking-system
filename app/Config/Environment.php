<?php

declare(strict_types=1);

namespace SpaBooking\Config;

use RuntimeException;

final class Environment
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new RuntimeException('Unable to read the environment file.');
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                throw new RuntimeException(sprintf('Invalid environment entry on line %d.', $lineNumber + 1));
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));

            if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $name) !== 1) {
                throw new RuntimeException(sprintf('Invalid environment key on line %d.', $lineNumber + 1));
            }

            if (getenv($name) !== false) {
                continue;
            }

            $value = self::unquote($value);
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);

        return $value === false ? $default : $value;
    }

    public static function bool(string $name, bool $default = false): bool
    {
        $value = self::get($name);

        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new RuntimeException(sprintf('%s must be a boolean value.', $name));
        }

        return $parsed;
    }

    private static function unquote(string $value): string
    {
        if (strlen($value) < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
