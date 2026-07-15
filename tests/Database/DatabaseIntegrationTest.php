<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Database;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Config\Environment;
use SpaBooking\Database\DatabaseSeeder;
use SpaBooking\Database\MigrationRunner;
use SpaBooking\Database\PdoConnectionFactory;
use SpaBooking\Repositories\AdminUserRepository;
use SpaBooking\Repositories\AppointmentRepository;
use SpaBooking\Repositories\ServiceRepository;
use SpaBooking\Repositories\TherapistRepository;

#[Group('database')]
final class DatabaseIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;

    private static ?MigrationRunner $migrations = null;

    protected function setUp(): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $root = dirname(__DIR__, 2);
        Environment::load($root . '/.env');

        if (getenv('RUN_DATABASE_TESTS') !== 'true') {
            self::markTestSkipped('Set RUN_DATABASE_TESTS=true to run MySQL integration tests.');
        }

        $database = getenv('DB_TEST_DATABASE');

        if (!is_string($database) || !str_ends_with($database, '_test')) {
            throw new RuntimeException('DB_TEST_DATABASE must name a dedicated database ending in _test.');
        }

        /** @var array{host: string, port: int, database: string, username: string,
         *     password: string, charset: string, options: array<int, mixed>} $config */
        $config = require $root . '/config/database.php';
        $config['database'] = $database;
        self::$pdo = (new PdoConnectionFactory($config))->create();
        self::$migrations = new MigrationRunner(self::$pdo, $root . '/database/migrations');

        $table = self::$pdo->query("SHOW TABLES LIKE 'migrations'")->fetchColumn();

        if ($table !== false) {
            $count = (int) self::$pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();

            if ($count > 0) {
                throw new RuntimeException('The integration test database must start empty.');
            }
        }

        self::$migrations->migrate();
        (new DatabaseSeeder(self::$pdo))->seed();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$migrations === null || self::$pdo === null) {
            return;
        }

        self::$migrations->rollback();
        self::$pdo->exec('DROP TABLE migrations');
        self::$migrations = null;
        self::$pdo = null;
    }

    public function testMigrationsAndSeedsAreRepeatable(): void
    {
        self::assertNotNull(self::$pdo);
        (new DatabaseSeeder(self::$pdo))->seed();

        self::assertSame(3, (int) self::$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn());
        self::assertSame(3, (int) self::$pdo->query('SELECT COUNT(*) FROM therapists')->fetchColumn());
        self::assertSame(6, (int) self::$pdo->query('SELECT COUNT(*) FROM therapist_services')->fetchColumn());
        self::assertSame(15, (int) self::$pdo->query('SELECT COUNT(*) FROM therapist_availability')->fetchColumn());
        self::assertSame(2, (int) self::$pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn());
        self::assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn());
        self::assertSame([], self::$migrations?->migrate());
    }

    public function testRepositoriesMapSeededCatalogAndQualifications(): void
    {
        self::assertNotNull(self::$pdo);
        $services = (new ServiceRepository(self::$pdo))->findActive();
        $therapists = (new TherapistRepository(self::$pdo))->findActiveQualifiedForService(1);
        $availability = (new TherapistRepository(self::$pdo))->findAvailability(1, 1);

        self::assertSame('stillwater-massage', $services[0]->slug);
        self::assertSame(['mara-vale', 'theo-linden'], array_column($therapists, 'slug'));
        self::assertSame('09:00:00', $availability[0]->startsAt);
    }

    public function testAppointmentAndAdministratorQueriesUseMappedDomainData(): void
    {
        self::assertNotNull(self::$pdo);
        $appointments = new AppointmentRepository(self::$pdo);
        $appointment = $appointments->findByReference('DEMO00000001');
        $administrator = (new AdminUserRepository(self::$pdo))->findActiveByEmail('ADMIN@EXAMPLE.TEST');

        self::assertNotNull($appointment);
        self::assertSame('Stillwater Massage', $appointment->serviceName);
        self::assertTrue($appointments->hasBlockingOverlap(
            1,
            new DateTimeImmutable('2030-06-03 15:30:00'),
            new DateTimeImmutable('2030-06-03 16:30:00')
        ));
        self::assertNotNull($administrator);
        self::assertTrue(password_verify('SpaDemo!2026', $administrator->passwordHash));
    }
}
