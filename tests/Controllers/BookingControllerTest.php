<?php

declare(strict_types=1);

namespace SpaBooking\Tests\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Controllers\BookingController;
use SpaBooking\Models\Service;
use SpaBooking\Models\Therapist;
use SpaBooking\Models\TherapistAvailability;
use SpaBooking\Repositories\ServiceCatalogRepository;
use SpaBooking\Repositories\AppointmentScheduleRepository;
use SpaBooking\Repositories\AvailabilityRepository;
use SpaBooking\Repositories\TherapistCatalogRepository;
use SpaBooking\Security\CsrfTokenManager;
use SpaBooking\Services\AvailabilityService;
use SpaBooking\Services\BookingDraftStore;
use SpaBooking\Validation\CustomerDetailsValidator;
use SpaBooking\Validation\TimeSelectionValidator;
use SpaBooking\View\ViewRenderer;

final class BookingControllerTest extends TestCase
{
    private ViewRenderer $views;

    /** @var array<string, mixed> */
    private array $session = [];

    protected function setUp(): void
    {
        $this->views = new ViewRenderer(dirname(__DIR__, 2) . '/app/Views');
    }

    public function testItRendersTheBookingEntryAndSelectedServiceSummary(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Forest Facial', $response->body());
        self::assertStringContainsString('A calming facial.', $response->body());
        self::assertStringContainsString('50 minutes', $response->body());
        self::assertStringContainsString('$86.50', $response->body());
        self::assertStringContainsString('Back to service details', $response->body());
        self::assertStringContainsString('aria-current="step"', $response->body());
        self::assertStringContainsString('<span>1</span><b>Therapist</b>', $response->body());
        self::assertStringContainsString('aria-disabled="true"', $response->body());
        self::assertStringNotContainsString('#booking-flow', strstr($response->body(), '<header', true));
    }

    public function testItOffersAnyAndSpecificQualifiedTherapistChoices(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7');

        self::assertStringContainsString('Any available therapist', $response->body());
        self::assertStringContainsString('Mara Vale', $response->body());
        self::assertStringContainsString('Restorative facial specialist.', $response->body());
        self::assertStringContainsString('value="3"', $response->body());
    }

    public function testItShowsAFriendlyStateWithoutQualifiedTherapists(): void
    {
        $response = $this->controller($this->service(), [])->start('7');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('No qualified therapists', $response->body());
    }

    public function testItAcceptsAValidDateAndPreservesTherapistSelection(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7', [
            'step' => 'datetime',
            'therapist' => '3',
            'date' => '2030-06-03',
        ]);

        self::assertSame(200, $response->status());
        self::assertStringContainsString('value="2030-06-03"', $response->body());
        self::assertStringContainsString('value="3"', $response->body());
        self::assertStringContainsString('Therapist preference:</strong>', $response->body());
        self::assertStringContainsString('name="time"', $response->body());
    }

    public function testItRendersSelectableSlotsAndPreservesAValidSelectedTime(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7', [
            'step' => 'details',
            'therapist' => '3',
            'date' => '2030-06-03',
            'time' => '09:30',
        ]);

        self::assertSame(200, $response->status());
        self::assertStringContainsString('name="time"', $response->body());
        self::assertStringContainsString('value="09:30"', $response->body());
        self::assertStringContainsString('<span>3</span><b>Your Details</b>', $response->body());
        self::assertStringContainsString('class="customer-details-form"', $response->body());
        self::assertStringContainsString('name="email"', $response->body());
        self::assertStringContainsString('name="_token"', $response->body());
    }

    public function testItRejectsMalformedAndUnavailableTimesWithoutShowingCustomerDetails(): void
    {
        $controller = $this->controller($this->service(), [$this->therapist()]);
        $query = ['step' => 'details', 'therapist' => '3', 'date' => '2030-06-03'];
        $malformed = $controller->start('7', $query + ['time' => '9:30']);
        $unavailable = $controller->start('7', $query + ['time' => '17:00']);

        self::assertStringContainsString('Choose a valid time in HH:MM format.', $malformed->body());
        self::assertStringContainsString('That time is not currently available.', $unavailable->body());
        self::assertStringNotContainsString('name="email"', $malformed->body());
        self::assertStringNotContainsString('name="email"', $unavailable->body());
    }

    public function testAnyTherapistSelectionRetainsEveryCandidateForTheChosenSlot(): void
    {
        $second = new Therapist(4, 'Theo Linden', 'theo-linden', 'Massage specialist.', true, 2);
        $response = $this->controller($this->service(), [$this->therapist(), $second])->start('7', [
            'step' => 'details',
            'therapist' => 'any',
            'date' => '2030-06-03',
            'time' => '09:00',
        ]);

        self::assertStringContainsString('value="any"', $response->body());
        self::assertStringContainsString('name="time"', $response->body());
        self::assertStringContainsString('value="09:00"', $response->body());
    }

    public function testItRendersProgressAndFragmentActionsAfterTherapistSelection(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7', [
            'step' => 'details',
            'therapist' => 'any',
            'date' => '2030-06-03',
            'time' => '09:00',
        ]);

        self::assertStringContainsString('class="is-complete"', $response->body());
        self::assertMatchesRegularExpression('/class="is-active"\s+aria-current="step"/', $response->body());
        self::assertStringContainsString('action="/book/7#booking-flow"', $response->body());
        self::assertStringContainsString('id="booking-flow"', $response->body());
    }

    public function testOnlyTheValidatedActiveStepPanelIsRendered(): void
    {
        $controller = $this->controller($this->service(), [$this->therapist()]);
        $initial = $controller->start('7', ['step' => 'datetime']);
        $dateTime = $controller->start('7', ['step' => 'datetime', 'therapist' => 'any']);
        $blockedDetails = $controller->start('7', ['step' => 'details', 'therapist' => 'any']);

        self::assertStringContainsString('id="therapist-heading"', $initial->body());
        self::assertStringContainsString('id="datetime-heading"', $dateTime->body());
        self::assertStringContainsString('id="datetime-heading"', $blockedDetails->body());
        self::assertSame(1, substr_count($dateTime->body(), 'class="wizard-panel"'));
        self::assertStringNotContainsString('id="details-heading"', $dateTime->body());
    }

    public function testWizardUsesOneForwardActionAndNeverPlacesCustomerDetailsInUrls(): void
    {
        $response = $this->controller($this->service(), [$this->therapist()])->start('7', [
            'step' => 'details',
            'therapist' => 'any',
            'date' => '2030-06-03',
            'time' => '09:00',
        ]);

        self::assertSame(1, substr_count($response->body(), 'Review booking</button>'));
        self::assertStringContainsString('action="/book/7#booking-flow"', $response->body());
        self::assertStringNotContainsString('?name=', $response->body());
        self::assertStringNotContainsString('&amp;email=', $response->body());
    }

    public function testBackNavigationPreservesCustomerDraftValues(): void
    {
        $controller = $this->controller($this->service(), [$this->therapist()]);
        $token = (new CsrfTokenManager($this->session))->token();
        $input = $this->validReviewInput($token);
        $input['step'] = 'datetime';
        $input['name'] = 'Avery Preserved';
        $controller->review('7', $input);

        $response = $controller->start('7', [
            'step' => 'details',
            'therapist' => 'any',
            'date' => '2030-06-03',
            'time' => '09:00',
        ]);

        self::assertStringContainsString('value="Avery Preserved"', $response->body());
    }

    public function testItRejectsInvalidAndPastDates(): void
    {
        $invalid = $this->controller($this->service(), [$this->therapist()])->start('7', [
            'step' => 'datetime',
            'therapist' => 'any',
            'date' => '06/03/2030',
        ]);
        $past = $this->controller($this->service(), [$this->therapist()])->start('7', [
            'step' => 'datetime',
            'therapist' => 'any',
            'date' => '2030-05-31',
        ]);

        self::assertStringContainsString('Choose a valid date in YYYY-MM-DD format.', $invalid->body());
        self::assertStringContainsString('Choose today or a future date.', $past->body());
    }

    public function testValidCustomerDetailsRenderTheNonPersistingReviewStep(): void
    {
        $controller = $this->controller($this->service(), [$this->therapist()]);
        $token = (new CsrfTokenManager($this->session))->token();
        $response = $controller->review('7', $this->validReviewInput($token));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('aria-current="step"', $response->body());
        self::assertStringContainsString('Your appointment has not been booked yet.', $response->body());
        self::assertStringContainsString('Confirm booking — coming next', $response->body());
        self::assertStringContainsString('Avery Reed', $response->body());
        self::assertStringContainsString('Any available therapist', $response->body());
        self::assertStringContainsString('data-booking-focus', $response->body());
    }

    public function testInvalidCustomerDetailsShowAccessibleErrorsAndPreserveSafeValues(): void
    {
        $controller = $this->controller($this->service(), [$this->therapist()]);
        $token = (new CsrfTokenManager($this->session))->token();
        $input = $this->validReviewInput($token);
        $input['name'] = '  <b>A</b>  ';
        $input['email'] = 'invalid';
        $input['phone'] = '';
        $input['notes'] = str_repeat('x', 1001);
        $response = $controller->review('7', $input);

        self::assertSame(422, $response->status());
        self::assertStringContainsString('class="error-summary"', $response->body());
        self::assertStringContainsString('aria-invalid="true"', $response->body());
        self::assertStringContainsString('value="&lt;b&gt;A&lt;/b&gt;"', $response->body());
        self::assertStringNotContainsString('value="<b>A</b>"', $response->body());
        self::assertStringContainsString('data-booking-focus', $response->body());
    }

    public function testInvalidCsrfTokenIsRejectedSafely(): void
    {
        $controller = $this->controller($this->service(), [$this->therapist()]);
        (new CsrfTokenManager($this->session))->token();
        $response = $controller->review('7', $this->validReviewInput('invalid'));

        self::assertSame(419, $response->status());
        self::assertStringContainsString('Your session check failed.', $response->body());
        self::assertStringNotContainsString('Your appointment has not been booked yet.', $response->body());
    }

    public function testItReturnsNotFoundForAnInvalidServiceId(): void
    {
        $response = $this->controller($this->service(), [])->start('invalid');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('That page is resting', $response->body());
    }

    public function testItReturnsNotFoundForAMissingOrInactiveService(): void
    {
        $response = $this->controller(null, [])->start('7');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('That page is resting', $response->body());
    }

    public function testItHandlesRepositoryFailuresWithoutExposingDetails(): void
    {
        $services = new class implements ServiceCatalogRepository {
            public function findActive(): array
            {
                return [];
            }

            public function findActiveById(int $id): ?Service
            {
                throw new RuntimeException('SQLSTATE password=secret internal-host');
            }
        };

        $response = (new BookingController(
            $this->views,
            $services,
            $this->therapistRepository([]),
            $this->availabilityRepository(),
            $this->appointmentRepository(),
            new AvailabilityService(),
            new DateTimeZone('America/Chicago'),
            new CsrfTokenManager($this->session),
            new CustomerDetailsValidator(),
            new TimeSelectionValidator(),
            new BookingDraftStore($this->session),
            new DateTimeImmutable('2030-06-01', new DateTimeZone('America/Chicago'))
        ))->start('7');

        self::assertSame(503, $response->status());
        self::assertStringContainsString('Booking is taking a short rest', $response->body());
        self::assertStringNotContainsString('SQLSTATE', $response->body());
        self::assertStringNotContainsString('secret', $response->body());
        self::assertStringNotContainsString('internal-host', $response->body());
    }

    /** @param list<Therapist> $therapists */
    private function controller(?Service $service, array $therapists): BookingController
    {
        $services = new class ($service) implements ServiceCatalogRepository {
            public function __construct(private readonly ?Service $service)
            {
            }

            public function findActive(): array
            {
                return $this->service === null ? [] : [$this->service];
            }

            public function findActiveById(int $id): ?Service
            {
                return $this->service?->id === $id && $this->service->isActive ? $this->service : null;
            }
        };

        return new BookingController(
            $this->views,
            $services,
            $this->therapistRepository($therapists),
            $this->availabilityRepository(),
            $this->appointmentRepository(),
            new AvailabilityService(),
            new DateTimeZone('America/Chicago'),
            new CsrfTokenManager($this->session),
            new CustomerDetailsValidator(),
            new TimeSelectionValidator(),
            new BookingDraftStore($this->session),
            new DateTimeImmutable('2030-06-01', new DateTimeZone('America/Chicago'))
        );
    }

    /** @param list<Therapist> $therapists */
    private function therapistRepository(array $therapists): TherapistCatalogRepository
    {
        return new class ($therapists) implements TherapistCatalogRepository {
            /** @param list<Therapist> $therapists */
            public function __construct(private readonly array $therapists)
            {
            }

            public function findActiveQualifiedForService(int $serviceId): array
            {
                return $this->therapists;
            }
        };
    }

    private function availabilityRepository(): AvailabilityRepository
    {
        return new class implements AvailabilityRepository {
            public function findAvailability(int $therapistId, int $dayOfWeek): array
            {
                return [new TherapistAvailability(1, $therapistId, $dayOfWeek, '09:00', '11:00')];
            }

            public function findAvailabilityExceptions(int $therapistId, string $date): array
            {
                return [];
            }
        };
    }

    private function appointmentRepository(): AppointmentScheduleRepository
    {
        return new class implements AppointmentScheduleRepository {
            public function findOverlappingAppointments(
                int $therapistId,
                DateTimeImmutable $startsAt,
                DateTimeImmutable $endsAt
            ): array {
                return [];
            }
        };
    }

    private function service(): Service
    {
        return new Service(7, 'Forest Facial', 'forest-facial', 'A calming facial.', 50, 8650, true, 1);
    }

    private function therapist(): Therapist
    {
        return new Therapist(3, 'Mara Vale', 'mara-vale', 'Restorative facial specialist.', true, 1);
    }

    /** @return array<string, string> */
    private function validReviewInput(string $token): array
    {
        return [
            '_token' => $token,
            'therapist' => 'any',
            'date' => '2030-06-03',
            'time' => '09:00',
            'name' => ' Avery Reed ',
            'email' => 'avery@example.test',
            'phone' => '555-0102',
            'notes' => 'Quiet room, please.',
        ];
    }
}
