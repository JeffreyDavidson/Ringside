<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Queries\Storylines\StorylineSourcePlacementQuery;

test('says whether the event still has the match in one statement', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    $other = EventMatch::factory()
        ->create();
    $query = resolve(StorylineSourcePlacementQuery::class);

    // Act
    $has = $query->get($event, $match->id);
    $hasOther = $query->get($event, $other->id);
    $statements = recordStatements(fn () => $query->get($event, $match->id));

    // Assert
    expect($has)->toBeTrue()
        ->and($hasOther)->toBeFalse()
        ->and($statements)->toHaveCount(1);
});

test('does not count a match that was deleted', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    $match->delete();

    // Act
    $has = resolve(StorylineSourcePlacementQuery::class)->get($event, $match->id);

    // Assert
    expect($has)->toBeFalse();
});
