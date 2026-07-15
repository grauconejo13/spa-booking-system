<?php

declare(strict_types=1);

use SpaBooking\Database\Migration;

return new class implements Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                description TEXT NOT NULL,
                duration_minutes SMALLINT UNSIGNED NOT NULL,
                price_cents INT UNSIGNED NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_services_active_order (is_active, display_order),
                CONSTRAINT chk_services_duration CHECK (duration_minutes > 0),
                CONSTRAINT chk_services_price CHECK (price_cents > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE therapists (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                bio TEXT NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_therapists_active_order (is_active, display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE therapist_services (
                therapist_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (therapist_id, service_id),
                INDEX idx_therapist_services_service (service_id, therapist_id),
                CONSTRAINT fk_therapist_services_therapist FOREIGN KEY (therapist_id)
                    REFERENCES therapists (id) ON DELETE CASCADE,
                CONSTRAINT fk_therapist_services_service FOREIGN KEY (service_id)
                    REFERENCES services (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE therapist_services');
        $pdo->exec('DROP TABLE therapists');
        $pdo->exec('DROP TABLE services');
    }
};
