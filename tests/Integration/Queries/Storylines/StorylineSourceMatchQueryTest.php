<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Storylines\StorylineSourceMatchQuery;

test('reads a match of the event with its competitors and titles', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors(Wrestler::factory()->count(2)->create()->all())
        ->create();
    $query = resolve(StorylineSourceMatchQuery::class);

    // Act
    $found = $query->get($event->id, $match->id);
    $statements = recordStatements(fn () => $query->get($event->id, $match->id));

    // Assert
    expect($found?->is($match))->toBeTrue()
        ->and($found?->competitors)->toHaveCount(2)
        ->and($statements)->toHaveCount(3);
});

test('keeps reading a match that was deleted', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    $match->delete();

    // Act
    $found = resolve(StorylineSourceMatchQuery::class)->get($event->id, $match->id);

    // Assert
    expect($found?->trashed())->toBeTrue();
});

test('reads nothing for a match of another event', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $other = EventMatch::factory()
        ->create();

    // Act
    $found = resolve(StorylineSourceMatchQuery::class)->get($event->id, $other->id);

    // Assert
    expect($found)->toBeNull();
});
