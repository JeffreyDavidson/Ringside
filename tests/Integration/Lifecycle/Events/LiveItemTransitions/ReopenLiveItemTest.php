<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/live-show.php';

use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveItemTransitions\ReopenLiveItem;

test('it reopens a finished item as running and a skipped item as pending', function (string $from, string $to): void {
    // Arrange
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => $from, 'start' => '2026-10-19T18:00:00+00:00', 'finish' => '2026-10-19T18:20:00+00:00']]);

    // Act
    $item = resolve(ReopenLiveItem::class)->apply($run, 0, null, null);

    // Assert
    expect($item['state'])->toBe($to)
        ->and($item['start'])->toBe('2026-10-19T18:00:00+00:00')
        ->and($item['finish'])->toBeNull();
})->with([
    'finished' => ['finished', 'running'],
    'skipped' => ['skipped', 'pending'],
]);

test('it refuses an item that is still open, while another item runs or once a later item has started', function (array $items, int $index): void {
    // Arrange
    $run = withLiveItems(liveShowFixture(segment: true)['run'], $items);

    // Act
    $reopen = fn () => resolve(ReopenLiveItem::class)->apply($run, $index, null, null);

    // Assert
    expect($reopen)->toThrow(LiveShowException::class, __('live-show.transition_invalid'));
})->with([
    'pending' => [[], 0],
    'running' => [[0 => ['state' => 'running', 'start' => '2026-10-19T18:00:00+00:00']], 0],
    'another item running' => [[0 => ['state' => 'running', 'start' => '2026-10-19T18:00:00+00:00'], 1 => ['state' => 'finished', 'start' => null]], 1],
    'a later item started' => [[0 => ['state' => 'finished', 'start' => '2026-10-19T18:00:00+00:00'], 1 => ['state' => 'finished', 'start' => '2026-10-19T18:30:00+00:00']], 0],
]);
