<?php

declare(strict_types=1);

namespace SpaBooking\Database;

use PDO;
use PDOException;

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

        try {
            return new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException) {
            throw new DatabaseConnectionException('Unable to connect to the database.');
        }
    }
}
