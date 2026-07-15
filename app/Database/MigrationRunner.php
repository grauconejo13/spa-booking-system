<?php

declare(strict_types=1);

namespace SpaBooking\Database;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationPath
    ) {
    }

    /** @return list<string> */
    public function migrate(): array
    {
        $this->ensureLedger();
        $applied = $this->appliedNames();
        $pending = array_values(array_diff(array_keys($this->migrationFiles()), $applied));

        if ($pending === []) {
            return [];
        }

        $batch = $this->nextBatch();
        $record = $this->pdo->prepare(
            'INSERT INTO migrations (migration, batch, applied_at) VALUES (:migration, :batch, UTC_TIMESTAMP())'
        );
        assert($record !== false);

        foreach ($pending as $name) {
            $this->load($this->migrationFiles()[$name])->up($this->pdo);
            $record->execute(['migration' => $name, 'batch' => $batch]);
        }

        return $pending;
    }

    /** @return list<string> */
    public function rollback(): array
    {
        $this->ensureLedger();
        $statement = $this->pdo->query(
            'SELECT migration FROM migrations WHERE batch = (SELECT MAX(batch) FROM migrations) ORDER BY id DESC'
        );
        assert($statement !== false);
        $names = $statement->fetchAll(PDO::FETCH_COLUMN);

        if ($names === []) {
            return [];
        }

        $files = $this->migrationFiles();
        $delete = $this->pdo->prepare('DELETE FROM migrations WHERE migration = :migration');
        assert($delete !== false);

        foreach ($names as $name) {
            if (!is_string($name) || !isset($files[$name])) {
                throw new RuntimeException('An applied migration file is missing.');
            }

            $this->load($files[$name])->down($this->pdo);
            $delete->execute(['migration' => $name]);
        }

        return array_values(array_filter($names, 'is_string'));
    }

    private function ensureLedger(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return list<string> */
    private function appliedNames(): array
    {
        $statement = $this->pdo->query('SELECT migration FROM migrations ORDER BY id');
        assert($statement !== false);
        $names = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_filter($names, 'is_string'));
    }

    private function nextBatch(): int
    {
        $statement = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations');
        assert($statement !== false);
        $value = $statement->fetchColumn();

        return (int) $value;
    }

    /** @return array<string, string> */
    private function migrationFiles(): array
    {
        $files = glob(rtrim($this->migrationPath, '/\\') . '/*.php');

        if ($files === false) {
            throw new RuntimeException('Unable to read migration files.');
        }

        sort($files, SORT_STRING);
        $migrations = [];

        foreach ($files as $file) {
            $migrations[pathinfo($file, PATHINFO_FILENAME)] = $file;
        }

        return $migrations;
    }

    private function load(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new RuntimeException(sprintf('Migration %s is invalid.', basename($file)));
        }

        return $migration;
    }
}
