<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Support\Carbon;

test('an event has a name', function () {
    $event = Event::factory()->create(['name' => 'Example Event Name']);

    expect($event)->name->toBe('Example Event Name');
});

test('an event has a date', function () {
    $event = Event::factory()->create(['date' => '2022-10-11 07:00:00']);

    expect($event)->date->toDateTimeString()->toBe('2022-10-11 07:00:00');
});

test('an event takes place at a venue', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);

    expect($event)->venue_id->toEqual($venue->id);
});

test('an event with a date in the future is scheduled', function () {
    $event = Event::factory()->create(['date' => Carbon::now()->addDay()->toDateTimeString()]);

    expect($event->hasFutureDate())->toBeTrue();
});

test('an event without a date is unscheduled', function () {
    $event = Event::factory()->create(['date' => null]);

    expect($event->isUnscheduled())->toBeTrue();
});

test('an event with a date in the past has past', function () {
    $event = Event::factory()->create(['date' => Carbon::now()->subDay()->toDateTimeString()]);

    expect($event->hasPastDate())->toBeTrue();
});
