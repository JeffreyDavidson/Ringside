<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Events\Venue;

test('it rejects creating events at the same venue and time', function () {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();
    Event::factory()->for($venue)->create(['date' => $date]);
    $data = new EventData('Conflicting Event', $date, $venue, null);

    expect(fn () => resolve(CreateAction::class)->handle($data))
        ->toThrow(
            SchedulingConflictException::class,
            "Venue [{$venue->name}] is already booked at this event time.",
        );

    expect(Event::query()->where('name', 'Conflicting Event')->exists())->toBeFalse();
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
        ->toThrow(SchedulingConflictException::class);

    expect($event->refresh())
        ->name->not->toBe('Updated Event')
        ->venue_id->toBe($originalVenue->id);
});

test('it rejects rescheduling an event into a conflict at its current venue', function () {
    $originalDate = now()->addWeek();
    $conflictingDate = now()->addWeeks(2);
    $venue = Venue::factory()->create();
    $event = Event::factory()->for($venue)->create(['date' => $originalDate]);
    Event::factory()->for($venue)->create(['date' => $conflictingDate]);
    $data = new EventData('Rescheduled Event', $conflictingDate, $venue, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class);

    expect($event->refresh())
        ->name->not->toBe('Rescheduled Event')
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
