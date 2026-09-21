<?php

declare(strict_types=1);

use App\Exceptions\Events\CannotBeRescheduledException;
use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Models\Events\Event;

test('it rejects changing the date of a past event', function () {
    $event = Event::factory()->make([
        'name' => 'Summer Spectacular',
        'date' => now()->subWeek(),
    ]);

    expect(fn () => EventSchedulingEligibility::ensureDateCanChange($event, now()->addWeek()))
        ->toThrow(
            CannotBeRescheduledException::class,
            'Event [Summer Spectacular] cannot be rescheduled because it has already occurred.',
        );
});

test('it permits retaining the date of a past event', function () {
    $date = now()->subWeek();
    $event = Event::factory()->make(['date' => $date]);

    expect(fn () => EventSchedulingEligibility::ensureDateCanChange($event, $date->clone()))
        ->not->toThrow(CannotBeRescheduledException::class);
});

test('it permits changing the date of a future event', function () {
    $event = Event::factory()->make(['date' => now()->addWeek()]);

    expect(fn () => EventSchedulingEligibility::ensureDateCanChange($event, now()->addWeeks(2)))
        ->not->toThrow(CannotBeRescheduledException::class);
});
