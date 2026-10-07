<?php

declare(strict_types=1);

use SpaBooking\Config\Environment;
use SpaBooking\Controllers\AdminAuthController;
use SpaBooking\Controllers\AdminDashboardController;
use SpaBooking\Controllers\BookingController;
use SpaBooking\Controllers\BookingConfirmationController;
use SpaBooking\Controllers\BookingSubmissionController;
use SpaBooking\Controllers\HomeController;
use SpaBooking\Controllers\ServicesController;
use SpaBooking\Database\PdoConnectionFactory;
use SpaBooking\Http\ErrorHandler;
use SpaBooking\Http\Response;
use SpaBooking\Http\Router;
use SpaBooking\Repositories\AdminUserRepository;
use SpaBooking\Repositories\AppointmentRepository;
use SpaBooking\Repositories\ServiceRepository;
use SpaBooking\Repositories\TherapistRepository;
use SpaBooking\Security\AdminSession;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\AdminAuthService;
use SpaBooking\Services\InMemoryServiceCatalog;
use SpaBooking\Validation\CustomerDetailsValidator;
use SpaBooking\Validation\TimeSelectionValidator;
use SpaBooking\Services\AvailabilityService;
use SpaBooking\Services\BookingDraftStore;
use SpaBooking\Services\BookingReferenceGenerator;
use SpaBooking\Services\BookingService;
use SpaBooking\Services\BookingSubmissionStore;
use SpaBooking\Services\TherapistAssignmentService;
use SpaBooking\View\ViewRenderer;

$root = dirname(__DIR__);
$autoloadPath = $root . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Application dependencies are missing. Run composer install.';
    exit;
}

require $autoloadPath;

$views = new ViewRenderer($root . '/app/Views');
$errors = new ErrorHandler($views, false, $root . '/storage/logs/app.log');

try {
    Environment::load($root . '/.env');

    /** @var array{debug: bool, timezone: string, url: string} $appConfig */
    $appConfig = require $root . '/config/app.php';
    date_default_timezone_set($appConfig['timezone']);

    session_set_cookie_params([
        'httponly' => true,
        'secure' => str_starts_with($appConfig['url'], 'https://'),
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();

    $errors = new ErrorHandler($views, $appConfig['debug'], $root . '/storage/logs/app.log');
    $errors->register();

    /** @var array{host: string, port: int, database: string, username: string,
     *     password: string, charset: string, options: array<int, mixed>} $databaseConfig */
    $databaseConfig = require $root . '/config/database.php';

    $home = new HomeController($views, new InMemoryServiceCatalog());
    $services = static function () use ($databaseConfig, $views): Response {
        $repository = new ServiceRepository((new PdoConnectionFactory($databaseConfig))->create());

        return (new ServicesController($views, $repository))->index();
    };
    $serviceDetail = static function (string $id) use ($databaseConfig, $views): Response {
        $pdo = (new PdoConnectionFactory($databaseConfig))->create();

        return (new ServicesController(
            $views,
            new ServiceRepository($pdo),
            new TherapistRepository($pdo)
        ))->show($id);
    };
    /** @var array<string, mixed> $session */
    $session =& $_SESSION;
    $csrf = new CsrfTokenManager($session);
    $submissions = new BookingSubmissionStore($session);
    $bookingController = static function () use (
        $appConfig,
        $csrf,
        $databaseConfig,
        $views,
        $submissions,
        &$session
    ): BookingController {
        $pdo = (new PdoConnectionFactory($databaseConfig))->create();
        $therapists = new TherapistRepository($pdo);

        return new BookingController(
            $views,
            new ServiceRepository($pdo),
            $therapists,
            $therapists,
            new AppointmentRepository($pdo),
            new AvailabilityService(),
            new DateTimeZone($appConfig['timezone']),
            $csrf,
            new CustomerDetailsValidator(),
            new TimeSelectionValidator(),
            new BookingDraftStore($session),
            $submissions
        );
    };
    $bookingEntry = static fn (string $serviceId): Response =>
        $bookingController()->start($serviceId, $_GET);
    $bookingReview = static fn (string $serviceId): Response =>
        $bookingController()->review($serviceId, $_POST);
    $bookingConfirm = static function (string $serviceId) use (
        $appConfig,
        $csrf,
        $databaseConfig,
        $submissions
    ): Response {
        $pdo = (new PdoConnectionFactory($databaseConfig))->create();
        $therapists = new TherapistRepository($pdo);
        $appointments = new AppointmentRepository($pdo);
        $bookings = new BookingService(
            new ServiceRepository($pdo),
            $therapists,
            $therapists,
            $appointments,
            new AvailabilityService(),
            new TherapistAssignmentService(),
            new BookingReferenceGenerator(),
            new DateTimeZone($appConfig['timezone'])
        );

        return (new BookingSubmissionController(
            $csrf,
            new CustomerDetailsValidator(),
            $bookings,
            $submissions
        ))->confirm($serviceId, $_POST);
    };
    $bookingConfirmation = static function (string $reference) use (
        $appConfig,
        $databaseConfig,
        $views
    ): Response {
        $repository = new AppointmentRepository((new PdoConnectionFactory($databaseConfig))->create());

        return (new BookingConfirmationController(
            $views,
            $repository,
            new DateTimeZone($appConfig['timezone'])
        ))->show($reference);
    };
    $adminSession = new AdminSession($session);
    $adminAuthController = static function () use (
        $databaseConfig,
        $views,
        $adminSession,
        $csrf
    ): AdminAuthController {
        $repository = new AdminUserRepository((new PdoConnectionFactory($databaseConfig))->create());

        return new AdminAuthController(
            $views,
            new AdminAuthService($repository),
            $adminSession,
            $csrf
        );
    };
    $adminLoginForm = static function () use ($adminSession, $adminAuthController): Response {
        if ($adminSession->isAuthenticated(time())) {
            return new Response('', 303, ['Location' => '/admin']);
        }

        return $adminAuthController()->loginForm();
    };
    $adminLogin = static fn (): Response => $adminAuthController()->login($_POST);
    $adminDashboard = static function () use ($views, $adminSession, $csrf): Response {
        if (!$adminSession->isAuthenticated(time())) {
            return new Response('', 303, ['Location' => '/admin/login']);
        }

        return (new AdminDashboardController($views, $adminSession, $csrf))->index();
    };
    $adminLogout = static function () use ($adminSession, $adminAuthController): Response {
        if (!$adminSession->isAuthenticated(time())) {
            return new Response('', 303, ['Location' => '/admin/login']);
        }

        return $adminAuthController()->logout($_POST);
    };
    $router = new Router(
        static fn (): Response => new Response(
            $views->render('errors/404', ['title' => 'Page not found']),
            404
        )
    );

    /** @var callable(Router, HomeController, callable(): Response, callable(string): Response,
     *     callable(string): Response, callable(string): Response, callable(string): Response,
     *     callable(string): Response, callable(): Response, callable(): Response,
     *     callable(): Response, callable(): Response): void $registerRoutes */
    $registerRoutes = require $root . '/routes/web.php';
    $registerRoutes(
        $router,
        $home,
        $services,
        $serviceDetail,
        $bookingEntry,
        $bookingReview,
        $bookingConfirm,
        $bookingConfirmation,
        $adminLoginForm,
        $adminLogin,
        $adminDashboard,
        $adminLogout
    );

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $router->dispatch($method, $uri)->send();
} catch (Throwable $exception) {
    $errors->handle($exception);
}
