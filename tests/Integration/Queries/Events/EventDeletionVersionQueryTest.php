<?php

declare(strict_types=1);

use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Events\Event;
use App\Queries\Events\EventDeletionVersionQuery;

test('reads zero for an event that was never deleted or restored', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    // Act
    $version = resolve(EventDeletionVersionQuery::class)->get($event);

    // Assert
    expect($version)->toBe(0);
});

test('reads the id of the latest deletion or restoration of this event in one statement', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $other = Event::factory()
        ->create();
    resolve(DeletionStateManager::class)->delete($event, now()->subDay());
    resolve(DeletionStateManager::class)->restore($event, now()->subHour());
    resolve(DeletionStateManager::class)->delete($other, now());
    $latest = $event->lifecycleTransitions()->latest('id')->firstOrFail();
    $query = resolve(EventDeletionVersionQuery::class);

    // Act
    $version = $query->get($event);
    $statements = recordStatements(fn () => $query->get($event));

    // Assert
    expect($version)->toBe($latest->id)
        ->and($statements)->toHaveCount(1);
});
