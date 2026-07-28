<?php

declare(strict_types=1);

namespace SpaBooking\Tests\View;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SpaBooking\Models\Service;
use SpaBooking\Models\Therapist;
use SpaBooking\Models\TherapistAvailabilityState;
use SpaBooking\View\ViewRenderer;

final class ViewRendererTest extends TestCase
{
    private ViewRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ViewRenderer(dirname(__DIR__, 2) . '/app/Views');
    }

    public function testItRendersContentInsideTheSharedLayoutAndEscapesTheTitle(): void
    {
        $html = $this->renderer->render('errors/404', ['title' => '<script>alert(1)</script>']);

        self::assertStringContainsString('That page is resting', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<title><script>', $html);
    }

    public function testItRejectsViewTraversal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->renderer->render('../outside');
    }

    public function testServicesViewEscapesDatabaseValuesAndFormatsDetails(): void
    {
        $service = new Service(
            1,
            '<script>Forest Facial</script>',
            'forest-facial',
            '<img src=x onerror=alert(1)>',
            50,
            8650,
            true,
            1
        );

        $html = $this->renderer->render('services', [
            'title' => 'Spa services',
            'services' => [$service],
            'catalogError' => false,
        ]);

        self::assertStringContainsString('&lt;script&gt;Forest Facial&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        self::assertStringNotContainsString('<script>Forest Facial</script>', $html);
        self::assertStringContainsString('50 minutes', $html);
        self::assertStringContainsString('$86.50', $html);
    }

    public function testServiceDetailEscapesServiceAndTherapistValues(): void
    {
        $service = new Service(1, '<script>Facial</script>', 'facial', '<img src=x>', 50, 8650, true, 1);
        $therapist = new Therapist(1, '<b>Mara</b>', 'mara', '<svg onload=alert(1)>', true, 1);

        $html = $this->renderer->render('service-detail', [
            'title' => $service->name,
            'service' => $service,
            'therapists' => [$therapist],
            'detailError' => false,
        ]);

        self::assertStringContainsString('&lt;script&gt;Facial&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;img src=x&gt;', $html);
        self::assertStringContainsString('&lt;b&gt;Mara&lt;/b&gt;', $html);
        self::assertStringContainsString('&lt;svg onload=alert(1)&gt;', $html);
        self::assertStringNotContainsString('<svg onload=alert(1)>', $html);
    }

    public function testBookingEntryEscapesServiceAndTherapistValues(): void
    {
        $service = new Service(7, '<script>Facial</script>', 'facial', '<img src=x>', 50, 8650, true, 1);
        $therapist = new Therapist(3, '<b>Mara</b>', 'mara', '<svg onload=alert(1)>', true, 1);

        $html = $this->renderer->render('booking-entry', [
            'title' => 'Start booking',
            'service' => $service,
            'therapists' => [$therapist],
            'bookingError' => false,
            'selectedTherapist' => 'any',
            'selectedDate' => '',
            'dateError' => null,
            'slots' => [],
            'selectedTime' => '',
            'timeError' => null,
            'selectedSlot' => null,
            'hasTherapistSelection' => false,
            'therapistStates' => [],
            'customer' => ['name' => '', 'email' => '', 'phone' => '', 'notes' => ''],
            'formErrors' => [],
            'csrfToken' => 'test-token',
            'reviewReady' => false,
            'activeStep' => 'therapist',
        ]);

        self::assertStringContainsString('&lt;script&gt;Facial&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;img src=x&gt;', $html);
        self::assertStringContainsString('&lt;b&gt;Mara&lt;/b&gt;', $html);
        self::assertStringContainsString('&lt;svg onload=alert(1)&gt;', $html);
        self::assertStringNotContainsString('<svg onload=alert(1)>', $html);
    }

    public function testServiceDetailLinksToTheBookingEntry(): void
    {
        $service = new Service(7, 'Forest Facial', 'facial', 'A calming facial.', 50, 8650, true, 1);

        $html = $this->renderer->render('service-detail', [
            'title' => $service->name,
            'service' => $service,
            'therapists' => [],
            'detailError' => false,
        ]);

        self::assertStringContainsString('href="/book/7"', $html);
        self::assertStringContainsString('Start booking', $html);
    }

    public function testBookingEntryShowsDistinctUnavailableTherapistStatesAccessibly(): void
    {
        $service = new Service(7, 'Forest Facial', 'facial', 'A calming facial.', 50, 8650, true, 1);
        $therapists = [
            new Therapist(1, 'Not Scheduled', 'not-scheduled', 'Bio one.', true, 1),
            new Therapist(2, 'Fully Booked', 'fully-booked', 'Bio two.', true, 2),
            new Therapist(3, 'Closed Today', 'closed', 'Bio three.', true, 3),
        ];
        $states = [
            1 => new TherapistAvailabilityState(1, TherapistAvailabilityState::NOT_SCHEDULED, []),
            2 => new TherapistAvailabilityState(2, TherapistAvailabilityState::FULLY_BOOKED, []),
            3 => new TherapistAvailabilityState(3, TherapistAvailabilityState::UNAVAILABLE, []),
        ];

        $html = $this->renderer->render('booking-entry', [
            'title' => 'Start booking',
            'service' => $service,
            'therapists' => $therapists,
            'bookingError' => false,
            'selectedTherapist' => 'any',
            'selectedDate' => '2030-06-03',
            'dateError' => null,
            'slots' => [],
            'selectedTime' => '',
            'timeError' => null,
            'selectedSlot' => null,
            'hasTherapistSelection' => true,
            'therapistStates' => $states,
            'customer' => ['name' => '', 'email' => '', 'phone' => '', 'notes' => ''],
            'formErrors' => [],
            'csrfToken' => 'test-token',
            'reviewReady' => false,
            'activeStep' => 'therapist',
        ]);

        self::assertStringContainsString('<b>Not scheduled</b>', $html);
        self::assertStringContainsString('<b>Fully booked</b>', $html);
        self::assertStringContainsString('<b>Unavailable</b>', $html);
        self::assertStringContainsString('disabled aria-describedby="therapist-status-1"', $html);
        self::assertSame(1, substr_count($html, 'class="wizard-panel"'));
    }
}
