<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Carbon;

test('it rejects creating events at the same venue and time', function () {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();
    Event::factory()->for($venue)->create(['date' => $date]);
    $data = new EventData('Conflicting Event', $date, $venue, null);

    expect(fn () => resolve(CreateAction::class)->handle($data))
        ->toThrow(
            SchedulingConflictException::class,
            "Venue [{$venue->name}] is already booked on that day.",
        )
        ->and(Event::query()->where('name', 'Conflicting Event')->exists())->toBeFalse();
});

test('it permits using the same venue at a different time', function () {
    $venue = Venue::factory()->create();
    Event::factory()->for($venue)->create(['date' => now()->addWeek()]);
    $data = new EventData('Later Event', now()->addWeeks(2), $venue, null);

    $event = resolve(CreateAction::class)->handle($data);

    expect($event)
        ->name->toBe('Later Event')
        ->venue_id->toBe($venue->id);
});

test('it rejects moving an event into a venue scheduling conflict', function () {
    $date = now()->addWeek();
    $originalVenue = Venue::factory()->create();
    $conflictingVenue = Venue::factory()->create();
    $event = Event::factory()->for($originalVenue)->create(['date' => $date]);
    Event::factory()->for($conflictingVenue)->create(['date' => $date]);
    $data = new EventData('Updated Event', $date, $conflictingVenue, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class)->and($event->refresh())->name->not->toBe('Updated Event')->venue_id->toBe($originalVenue->id);
});

test('it rejects rescheduling an event into a conflict at its current venue', function () {
    $originalDate = now()->addWeek();
    $conflictingDate = now()->addWeeks(2);
    $venue = Venue::factory()->create();
    $event = Event::factory()->for($venue)->create(['date' => $originalDate]);
    Event::factory()->for($venue)->create(['date' => $conflictingDate]);
    $data = new EventData('Rescheduled Event', $conflictingDate, $venue, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class)->and($event->refresh())->name->not->toBe('Rescheduled Event')
        ->and($event->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it permits updating an event without changing its venue schedule', function () {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();
    $event = Event::factory()->for($venue)->create(['date' => $date]);
    $data = new EventData('Updated Event', $date, $venue, 'Updated preview');

    $updatedEvent = resolve(UpdateAction::class)->handle($event, $data);

    expect($updatedEvent)
        ->name->toBe('Updated Event')
        ->preview->toBe('Updated preview')
        ->venue_id->toBe($venue->id);
});

describe('events that already share a venue day', function () {
    it('permits editing the details and time of one without moving it to another day or venue', function () {
        // Arrange
        $venue = Venue::factory()->create();
        $day = now()->addWeek()->startOfDay();
        $matinee = Event::factory()->for($venue)->create(['name' => 'Matinee', 'date' => $day->copy()->setTime(13, 0)]);
        Event::factory()->for($venue)->create(['name' => 'Evening', 'date' => $day->copy()->setTime(20, 0)]);
        $data = new EventData('Matinee Renamed', $day->copy()->setTime(14, 0), $venue, 'Updated preview');

        // Act
        $updated = resolve(UpdateAction::class)->handle($matinee, $data);

        // Assert
        expect($updated)
            ->name->toBe('Matinee Renamed')
            ->preview->toBe('Updated preview')
            ->and($updated->date?->toDateTimeString())->toBe($day->copy()->setTime(14, 0)->toDateTimeString());
    });

    it('still rejects scheduling an unscheduled event at the venue onto a booked day', function () {
        // Arrange
        $venue = Venue::factory()->create();
        $date = now()->addWeek()->startOfDay()->setTime(12, 0);
        $event = Event::factory()->unscheduled()->for($venue)->create();
        Event::factory()->for($venue)->create(['date' => $date]);
        $data = new EventData('Scheduled Event', $date->copy()->addHour(), $venue, null);

        // Act
        $attempt = fn () => resolve(UpdateAction::class)->handle($event, $data);

        // Assert
        expect($attempt)->toThrow(SchedulingConflictException::class)
            ->and($event->refresh()->date)->toBeNull();
    });
});

test('it rejects restoring an event into a venue scheduling conflict', function () {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();
    $deletedEvent = Event::factory()->for($venue)->create(['date' => $date]);

    resolve(DeleteAction::class)->handle($deletedEvent);
    Event::factory()->for($venue)->create(['date' => $date]);

    expect(fn () => resolve(RestoreAction::class)->handle($deletedEvent))
        ->toThrow(
            SchedulingConflictException::class,
            "Venue [{$venue->name}] is already booked on that day.",
        )
        ->and(Event::onlyTrashed()->whereKey($deletedEvent->getKey())->exists())->toBeTrue();
});

function actAsPromotion(Promotion $promotion): void
{
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
}

describe('venue booking across promotions', function () {
    afterEach(function () {
        app(PromotionContextService::class)->clear();
    });

    it('rejects an event on the same day at a different time in another promotion without naming that event', function () {
        [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
        $venue = Venue::factory()->create();
        $day = now()->addWeek()->startOfDay();
        Event::factory()->for($venue)->for($promotionA, 'promotion')
            ->create(['name' => 'Secret Rival Show', 'date' => $day->copy()->setTime(20, 0)]);
        actAsPromotion($promotionB);
        $data = new EventData('Morning Show', $day->copy()->setTime(9, 0), $venue, null);

        $attempt = fn () => resolve(CreateAction::class)->handle($data);

        expect($attempt)->toThrow(
            SchedulingConflictException::class,
            sprintf('Venue [%s] is already booked on that day.', $venue->name),
        );
        try {
            $attempt();
        } catch (SchedulingConflictException $exception) {
            expect($exception->getMessage())->not->toContain('Secret Rival Show');
        }
    });

    it('permits the same venue on a different day', function () {
        [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
        $venue = Venue::factory()->create();
        $day = now()->addWeek()->startOfDay();
        Event::factory()->for($venue)->for($promotionA, 'promotion')
            ->create(['date' => $day->copy()->setTime(20, 0)]);
        actAsPromotion($promotionB);
        $data = new EventData('Next Day Show', $day->copy()->addDay()->setTime(9, 0), $venue, null);

        $event = resolve(CreateAction::class)->handle($data);

        expect($event->venue_id)->toBe($venue->id);
    });

    it('permits editing the event that already holds the venue day', function () {
        [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
        $venue = Venue::factory()->create();
        $day = now()->addWeek()->startOfDay();
        actAsPromotion($promotionA);
        $event = Event::factory()->for($venue)->for($promotionA, 'promotion')
            ->create(['date' => $day->copy()->setTime(20, 0)]);
        $data = new EventData('Renamed', $day->copy()->setTime(21, 0), $venue, null);

        $updated = resolve(UpdateAction::class)->handle($event, $data);

        expect($updated->name)->toBe('Renamed');
    });

    it('does not let a soft-deleted event in another promotion hold the venue day', function () {
        [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
        $venue = Venue::factory()->create();
        $day = now()->addWeek()->startOfDay();
        Event::factory()->for($venue)->for($promotionA, 'promotion')
            ->create(['date' => $day->copy()->setTime(20, 0)])
            ->delete();
        actAsPromotion($promotionB);
        $data = new EventData('Replacement Show', $day->copy()->setTime(9, 0), $venue, null);

        $event = resolve(CreateAction::class)->handle($data);

        expect($event->exists)->toBeTrue();
    });

    it('rejects restoring an event when another promotion booked the venue day meanwhile', function () {
        [$promotionA, $promotionB] = Promotion::factory()->count(2)->create()->all();
        $venue = Venue::factory()->create();
        $day = now()->addWeek()->startOfDay();
        $deleted = Event::factory()->for($venue)->for($promotionA, 'promotion')
            ->create(['date' => $day->copy()->setTime(20, 0)]);
        $deleted->delete();
        Event::factory()->for($venue)->for($promotionB, 'promotion')
            ->create(['date' => $day->copy()->setTime(10, 0)]);
        actAsPromotion($promotionA);

        expect(fn () => resolve(RestoreAction::class)->handle($deleted))
            ->toThrow(SchedulingConflictException::class);
    });
});

describe('venue day in the venue time zone', function () {
    // New York is UTC-4 in June, so its 10 June runs from 04:00 UTC on 10 June to 03:59 UTC on 11 June.
    it('conflicts for the same venue day even though the UTC dates differ', function () {
        // Arrange
        $venue = Venue::factory()->create(['timezone' => 'America/New_York']);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2030-06-11 03:00:00', 'UTC')]);
        $date = Carbon::parse('2030-06-10 14:00:00', 'UTC');

        // Act
        $attempt = fn () => VenueSchedulingEligibility::ensureAvailable($venue, $date);

        // Assert
        expect($attempt)->toThrow(SchedulingConflictException::class);
    });

    it('permits different venue days that share a UTC date', function () {
        // Arrange
        $venue = Venue::factory()->create(['timezone' => 'America/New_York']);
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2030-06-10 02:00:00', 'UTC')]);
        $date = Carbon::parse('2030-06-10 12:00:00', 'UTC');

        // Act
        VenueSchedulingEligibility::ensureAvailable($venue, $date);

        // Assert
        expect($venue->events()->count())->toBe(1);
    });

    it('judges a UTC venue by its UTC date', function () {
        // Arrange
        $venue = Venue::factory()->create();
        Event::factory()->for($venue)->create(['date' => Carbon::parse('2030-06-10 02:00:00', 'UTC')]);
        $date = Carbon::parse('2030-06-10 23:00:00', 'UTC');

        // Act
        $attempt = fn () => VenueSchedulingEligibility::ensureAvailable($venue, $date);

        // Assert
        expect($attempt)->toThrow(SchedulingConflictException::class);
    });

    it('treats a move across the venue local midnight as a new booking', function (string $newDate, bool $changing) {
        // Arrange
        $venue = Venue::factory()->create(['timezone' => 'America/New_York']);
        $event = Event::factory()->for($venue)->create(['date' => Carbon::parse('2030-06-11 03:30:00', 'UTC')]);

        // Act
        $result = VenueSchedulingEligibility::isBookingChanging($event, $venue, Carbon::parse($newDate, 'UTC'));

        // Assert
        expect($result)->toBe($changing);
    })->with([
        'past local midnight on the same UTC date' => ['2030-06-11 04:30:00', true],
        'earlier on the same local day across the UTC date' => ['2030-06-10 20:00:00', false],
    ]);
});
