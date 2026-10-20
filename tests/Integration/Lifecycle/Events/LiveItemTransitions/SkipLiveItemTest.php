<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/live-show.php';

use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveItemTransitions\SkipLiveItem;

test('it skips a pending item', function (): void {
    // Arrange
    $run = liveShowFixture()['run'];

    // Act
    $item = resolve(SkipLiveItem::class)->apply($run, 0, null, null);

    // Assert
    expect($item['state'])->toBe('skipped');
});

test('it refuses an item that is not pending', function (): void {
    // Arrange
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => 'running']]);

    // Act
    $skip = fn () => resolve(SkipLiveItem::class)->apply($run, 0, null, null);

    // Assert
    expect($skip)->toThrow(LiveShowException::class, __('live-show.transition_invalid'));
});

test('it refuses to skip before the show day', function (): void {
    // Arrange
    $run = liveShowFixture(date: now()->addDays(2)->toDateTimeString())['run'];

    // Act
    $skip = fn () => resolve(SkipLiveItem::class)->apply($run, 0, null, null);

    // Assert
    expect($skip)->toThrow(LiveShowException::class, __('live-show.show_day'));
});
