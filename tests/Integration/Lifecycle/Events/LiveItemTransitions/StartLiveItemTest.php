<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/live-show.php';

use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveItemTransitions\StartLiveItem;
use App\Models\Events\EventPack;

test('it starts the first pending item now', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);

    // Act
    $item = resolve(StartLiveItem::class)->apply($fixture['run'], 0, null, null);

    // Assert
    expect($item['state'])->toBe('running')
        ->and($item['start'])->toBe(now()->utc()->toIso8601String())
        ->and($item['key'])->toBe($fixture['key']);
});

test('it refuses an item that is not the next pending one', function (array $items, int $index): void {
    // Arrange
    $run = withLiveItems(liveShowFixture(segment: true)['run'], $items);

    // Act
    $start = fn () => resolve(StartLiveItem::class)->apply($run, $index, null, null);

    // Assert
    expect($start)->toThrow(LiveShowException::class, __('live-show.transition_invalid'));
})->with([
    'already running' => [[0 => ['state' => 'running']], 0],
    'another item running' => [[0 => ['state' => 'running']], 1],
    'a pending item comes first' => [[], 1],
]);

test('it asks for a review when the pack has moved on', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    EventPack::query()->forEventId($fixture['event']->id)->update(['version' => 2]);

    // Act
    $start = fn () => resolve(StartLiveItem::class)->apply($fixture['run'], 0, null, null);

    // Assert
    expect($start)->toThrow(LiveShowException::class, __('live-show.review_required'));
});

test('it refuses to start before the show day', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true, date: now()->addDays(2)->toDateTimeString());

    // Act
    $start = fn () => resolve(StartLiveItem::class)->apply($fixture['run'], 0, null, null);

    // Assert
    expect($start)->toThrow(LiveShowException::class, __('live-show.show_day'));
});
