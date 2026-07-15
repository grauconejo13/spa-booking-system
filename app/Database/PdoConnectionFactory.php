<?php

declare(strict_types=1);

namespace SpaBooking\Database;

use PDO;

final class PdoConnectionFactory
{
    /**
     * @param array{host: string, port: int, database: string, username: string,
     *     password: string, charset: string, options: array<int, mixed>} $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function create(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        return new PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options']
        );
    }
}

