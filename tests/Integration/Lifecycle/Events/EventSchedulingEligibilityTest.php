<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Exceptions\Events\CannotBeRescheduledException;
use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it rejects changing the date of a past event whose match has a recorded result', function (): void {
    // Arrange
    $event = Event::factory()
        ->create([
            'name' => 'Summer Spectacular',
            'date' => now()->subWeek(),
        ]);
    EventMatch::factory()
        ->forEvent($event)
        ->create(['match_finish' => MatchFinish::NoDecision]);

    // Act
    $change = fn () => EventSchedulingEligibility::ensureDateCanChange($event, now()->addWeek());

    // Assert
    expect($change)->toThrow(
        CannotBeRescheduledException::class,
        'Event [Summer Spectacular] cannot be rescheduled because results are recorded.',
    );
});

test('it rejects changing the date of a past event where a title changed hands', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => now()->subWeek()]);
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    TitleChampionship::factory()
        ->for(Title::factory()->active())
        ->wonAtEventMatch($match)
        ->create();

    // Act
    $change = fn () => EventSchedulingEligibility::ensureDateCanChange($event, now()->subDays(2));

    // Assert
    expect($change)->toThrow(CannotBeRescheduledException::class, 'title reigns');
});

test('it permits changing the date of a past event that has no recorded results', function (?int $targetDays): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => now()->subWeek()]);
    EventMatch::factory()
        ->forEvent($event)
        ->create();

    // Act
    $change = fn () => EventSchedulingEligibility::ensureDateCanChange(
        $event,
        $targetDays === null ? null : now()->addDays($targetDays),
    );

    // Assert
    expect($change)->not->toThrow(CannotBeRescheduledException::class);
})->with([
    'to a past date' => [-3],
    'to a future date' => [7],
    'unscheduled' => [null],
]);

test('it ignores a recorded result on a deleted match of a past event', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => now()->subWeek()]);
    EventMatch::factory()
        ->forEvent($event)
        ->create(['match_finish' => MatchFinish::NoDecision])
        ->delete();

    // Act
    $change = fn () => EventSchedulingEligibility::ensureDateCanChange($event, now()->addWeek());

    // Assert
    expect($change)->not->toThrow(CannotBeRescheduledException::class);
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
