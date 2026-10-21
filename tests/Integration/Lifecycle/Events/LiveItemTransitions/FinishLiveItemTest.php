<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/live-show.php';

use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveItemTransitions\FinishLiveItem;

test('it finishes the running item now', function (): void {
    // Arrange
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => 'running', 'start' => now()->subMinutes(5)->utc()->toIso8601String()]]);

    // Act
    $item = resolve(FinishLiveItem::class)->apply($run, 0, null, null);

    // Assert
    expect($item['state'])->toBe('finished')
        ->and($item['finish'])->toBe(now()->utc()->toIso8601String());
});

test('it refuses an item that is not running with a past start', function (string $state, ?int $startInMinutes): void {
    // Arrange
    $start = $startInMinutes === null ? null : now()->addMinutes($startInMinutes)->utc()->toIso8601String();
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => $state, 'start' => $start]]);

    // Act
    $finish = fn () => resolve(FinishLiveItem::class)->apply($run, 0, null, null);

    // Assert
    expect($finish)->toThrow(LiveShowException::class, __('live-show.transition_invalid'));
})->with([
    'pending' => ['pending', null],
    'running without a start' => ['running', null],
    'running with a future start' => ['running', 1],
]);
