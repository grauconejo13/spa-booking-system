<?php

declare(strict_types=1);

namespace SpaBooking\Database;

use PDO;
use Throwable;

final class DatabaseSeeder
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function seed(): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->seedServices();
            $this->seedTherapists();
            $this->seedQualifications();
            $this->seedAvailability();
            $this->seedAppointments();
            $this->seedAdministrator();
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function seedServices(): void
    {
        $sql = <<<'SQL'
            INSERT INTO services
                (id, name, slug, description, duration_minutes, price_cents, is_active, display_order,
                 created_at, updated_at)
            VALUES
                (1, 'Stillwater Massage', 'stillwater-massage',
                 'A fictional restorative full-body massage.', 60, 9500, 1, 10,
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00'),
                (2, 'Meadow Glow Facial', 'meadow-glow-facial',
                 'A fictional hydrating facial ritual.', 45, 7800, 1, 20,
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00'),
                (3, 'Quiet Stone Reset', 'quiet-stone-reset',
                 'A fictional warm-stone relaxation service.', 90, 13500, 1, 30,
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00')
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), slug = VALUES(slug), description = VALUES(description),
                duration_minutes = VALUES(duration_minutes), price_cents = VALUES(price_cents),
                is_active = VALUES(is_active), display_order = VALUES(display_order), updated_at = VALUES(updated_at)
            SQL;
        $this->pdo->exec($sql);
    }

    private function seedTherapists(): void
    {
        $sql = <<<'SQL'
            INSERT INTO therapists
                (id, name, slug, bio, is_active, display_order, created_at, updated_at)
            VALUES
                (1, 'Mara Vale', 'mara-vale',
                 'A fictional therapist who favors calm, restorative sessions.', 1, 10,
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00'),
                (2, 'Theo Linden', 'theo-linden',
                 'A fictional therapist focused on unhurried relaxation rituals.', 1, 20,
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00'),
                (3, 'Iris Shore', 'iris-shore',
                 'A fictional therapist specializing in facial and stone services.', 1, 30,
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00')
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), slug = VALUES(slug), bio = VALUES(bio), is_active = VALUES(is_active),
                display_order = VALUES(display_order), updated_at = VALUES(updated_at)
            SQL;
        $this->pdo->exec($sql);
    }

    private function seedQualifications(): void
    {
        $this->pdo->exec('DELETE FROM therapist_services WHERE therapist_id IN (1, 2, 3)');
        $this->pdo->exec(
            "INSERT INTO therapist_services (therapist_id, service_id, created_at) VALUES
            (1, 1, '2026-01-01 12:00:00'), (1, 2, '2026-01-01 12:00:00'),
            (2, 1, '2026-01-01 12:00:00'), (2, 3, '2026-01-01 12:00:00'),
            (3, 2, '2026-01-01 12:00:00'), (3, 3, '2026-01-01 12:00:00')"
        );
    }

    private function seedAvailability(): void
    {
        $this->pdo->exec('DELETE FROM therapist_availability WHERE therapist_id IN (1, 2, 3)');
        $values = [];

        foreach ([1, 2, 3] as $therapistId) {
            foreach ([1, 2, 3, 4, 5] as $day) {
                $values[] = sprintf(
                    "(%d, %d, '09:00:00', '17:00:00', '2026-01-01 12:00:00', '2026-01-01 12:00:00')",
                    $therapistId,
                    $day
                );
            }
        }

        $this->pdo->exec(
            'INSERT INTO therapist_availability '
            . '(therapist_id, day_of_week, starts_at, ends_at, created_at, updated_at) VALUES '
            . implode(', ', $values)
        );
    }

    private function seedAppointments(): void
    {
        $sql = <<<'SQL'
            INSERT INTO appointments
                (id, reference, service_id, therapist_id, service_name, duration_minutes, price_cents,
                 customer_name, customer_email, customer_phone, customer_note, starts_at, ends_at,
                 status, created_at, updated_at)
            VALUES
                (1, 'DEMO00000001', 1, 1, 'Stillwater Massage', 60, 9500, 'Demo Guest',
                 'guest.one@example.test', '555-0101', 'Fictional portfolio appointment.',
                 '2030-06-03 15:00:00', '2030-06-03 16:00:00', 'confirmed',
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00'),
                (2, 'DEMO00000002', 2, 3, 'Meadow Glow Facial', 45, 7800, 'Sample Visitor',
                 'guest.two@example.test', NULL, NULL,
                 '2030-06-04 18:00:00', '2030-06-04 18:45:00', 'pending',
                 '2026-01-01 12:00:00', '2026-01-01 12:00:00')
            ON DUPLICATE KEY UPDATE
                reference = VALUES(reference), service_id = VALUES(service_id), therapist_id = VALUES(therapist_id),
                service_name = VALUES(service_name), duration_minutes = VALUES(duration_minutes),
                price_cents = VALUES(price_cents), customer_name = VALUES(customer_name),
                customer_email = VALUES(customer_email), customer_phone = VALUES(customer_phone),
                customer_note = VALUES(customer_note), starts_at = VALUES(starts_at), ends_at = VALUES(ends_at),
                status = VALUES(status), updated_at = VALUES(updated_at)
            SQL;
        $this->pdo->exec($sql);
    }

    private function seedAdministrator(): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO admin_users
                (id, name, email, password_hash, is_active, last_login_at, created_at, updated_at)
             VALUES (1, :name, :email, :password_hash, 1, NULL, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email),
                password_hash = VALUES(password_hash), is_active = VALUES(is_active), updated_at = VALUES(updated_at)'
        );
        assert($statement !== false);
        $statement->execute([
            'name' => 'Demo Spa Administrator',
            'email' => 'admin@example.test',
            'password_hash' => password_hash('SpaDemo!2026', PASSWORD_DEFAULT),
            'created_at' => '2026-01-01 12:00:00',
            'updated_at' => '2026-01-01 12:00:00',
        ]);
    }
}
