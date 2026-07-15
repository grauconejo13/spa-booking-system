<?php

declare(strict_types=1);

use SpaBooking\Database\Migration;

return new class implements Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE therapist_availability (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                therapist_id BIGINT UNSIGNED NOT NULL,
                day_of_week TINYINT UNSIGNED NOT NULL,
                starts_at TIME NOT NULL,
                ends_at TIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_therapist_availability_window (therapist_id, day_of_week, starts_at, ends_at),
                INDEX idx_therapist_availability_lookup (therapist_id, day_of_week, starts_at, ends_at),
                CONSTRAINT chk_availability_day CHECK (day_of_week BETWEEN 1 AND 7),
                CONSTRAINT chk_availability_window CHECK (ends_at > starts_at),
                CONSTRAINT fk_availability_therapist FOREIGN KEY (therapist_id)
                    REFERENCES therapists (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            "CREATE TABLE appointments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reference CHAR(12) NOT NULL UNIQUE,
                service_id BIGINT UNSIGNED NOT NULL,
                therapist_id BIGINT UNSIGNED NOT NULL,
                service_name VARCHAR(120) NOT NULL,
                duration_minutes SMALLINT UNSIGNED NOT NULL,
                price_cents INT UNSIGNED NOT NULL,
                customer_name VARCHAR(120) NOT NULL,
                customer_email VARCHAR(254) NOT NULL,
                customer_phone VARCHAR(32) NULL,
                customer_note VARCHAR(1000) NULL,
                starts_at DATETIME NOT NULL,
                ends_at DATETIME NOT NULL,
                status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_appointments_collision (therapist_id, starts_at, ends_at, status),
                INDEX idx_appointments_admin (status, starts_at),
                INDEX idx_appointments_customer_email (customer_email),
                CONSTRAINT chk_appointments_duration CHECK (duration_minutes > 0),
                CONSTRAINT chk_appointments_price CHECK (price_cents > 0),
                CONSTRAINT chk_appointments_window CHECK (ends_at > starts_at),
                CONSTRAINT fk_appointments_service FOREIGN KEY (service_id)
                    REFERENCES services (id) ON DELETE RESTRICT,
                CONSTRAINT fk_appointments_therapist FOREIGN KEY (therapist_id)
                    REFERENCES therapists (id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE appointments');
        $pdo->exec('DROP TABLE therapist_availability');
    }
};
