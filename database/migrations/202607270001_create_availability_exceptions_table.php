<?php

declare(strict_types=1);

use SpaBooking\Database\Migration;

return new class implements Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE therapist_availability_exceptions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                therapist_id BIGINT UNSIGNED NOT NULL,
                exception_date DATE NOT NULL,
                is_available BOOLEAN NOT NULL,
                starts_at TIME NULL,
                ends_at TIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_availability_exception_lookup (therapist_id, exception_date),
                CONSTRAINT chk_availability_exception_window CHECK (
                    (is_available = 0 AND starts_at IS NULL AND ends_at IS NULL)
                    OR (is_available = 1 AND starts_at IS NOT NULL AND ends_at > starts_at)
                ),
                CONSTRAINT fk_availability_exception_therapist FOREIGN KEY (therapist_id)
                    REFERENCES therapists (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE therapist_availability_exceptions');
    }
};
