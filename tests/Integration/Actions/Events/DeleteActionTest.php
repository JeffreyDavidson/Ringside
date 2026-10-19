<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Exceptions\Events\CannotBeDeletedException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\assertNotSoftDeleted;

test('it soft deletes an event through a locked record', function () {
    $event = Event::factory()->create();
    $staleEvent = clone $event;

    $event->refresh();
    resolve(DeleteAction::class)->handle($staleEvent);

    expect(Event::query()->find($event->getKey()))->toBeNull()
        ->and(Event::withTrashed()->find($event->getKey()))->not->toBeNull();
});

test('it refuses to delete an event when one of its matches won or lost a title and writes nothing', function (bool $closesReign): void {
    // Arrange
    $event = Event::factory()->past()->create();
    EventMatch::factory()->forEvent($event)->create();
    $titleMatch = EventMatch::factory()->forEvent($event)->create();
    $reign = TitleChampionship::factory();
    $reign = $closesReign ? $reign->lostAtEventMatch($titleMatch) : $reign->wonAtEventMatch($titleMatch);
    $reign = $reign->create();

    // Act
    $delete = fn () => resolve(DeleteAction::class)->handle($event);

    // Assert
    expect($delete)->toThrow(CannotBeDeletedException::class, __('events.errors.deleted.has_title_change', ['name' => $event->name]))
        ->and($event->lifecycleTransitions()->count())->toBe(0)
        ->and(TitleChampionship::query()->whereKey($reign->id)->where('won_match_id', $reign->won_match_id)->where('lost_match_id', $reign->lost_match_id)->exists())->toBeTrue();

    assertNotSoftDeleted($event);
})->with([
    'a match that won the title' => [false],
    'a match that lost the title' => [true],
]);

test('it deletes an event whose matches changed no title', function (): void {
    // Arrange
    $event = Event::factory()->past()->create();
    EventMatch::factory()->forEvent($event)->create();

    // Act
    resolve(DeleteAction::class)->handle($event);

    // Assert
    expect($event->refresh()->trashed())->toBeTrue();
});
