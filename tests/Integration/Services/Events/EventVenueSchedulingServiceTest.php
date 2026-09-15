<?php

declare(strict_types=1);

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Services\Events\EventVenueSchedulingService;

test('it returns the scheduled venue when the venue is available', function (): void {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();

    $scheduledVenue = resolve(EventVenueSchedulingService::class)->schedule($date, $venue);

    expect($scheduledVenue?->is($venue))->toBeTrue();
});

test('it rejects scheduling a venue already used at the same time', function (): void {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();
    Event::factory()->for($venue)->create(['date' => $date]);

    expect(fn () => resolve(EventVenueSchedulingService::class)->schedule($date, $venue))
        ->toThrow(SchedulingConflictException::class, "Venue [{$venue->name}] is already booked at this event time.");
});
