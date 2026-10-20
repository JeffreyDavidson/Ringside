<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/live-show.php';

use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveItemTransitions\CorrectLiveItemTimes;

/** An ISO-8601 UTC time the given number of minutes from now. */
function minutesFromNow(int $minutes): string
{
    return now()->addMinutes($minutes)->utc()->toIso8601String();
}

test('it corrects the times of a finished item', function (): void {
    // Arrange
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => 'finished', 'start' => minutesFromNow(-30), 'finish' => minutesFromNow(-10)]]);

    // Act
    $item = resolve(CorrectLiveItemTimes::class)->apply($run, 0, now()->subMinutes(40), now()->subMinutes(20));

    // Assert
    expect($item['state'])->toBe('finished')
        ->and($item['start'])->toBe(minutesFromNow(-40))
        ->and($item['finish'])->toBe(minutesFromNow(-20));
});

test('it corrects the start of a running item and leaves it unfinished', function (): void {
    // Arrange
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => 'running', 'start' => minutesFromNow(-5)]]);

    // Act
    $item = resolve(CorrectLiveItemTimes::class)->apply($run, 0, now()->subMinutes(15), null);

    // Assert
    expect($item['start'])->toBe(minutesFromNow(-15))
        ->and($item['finish'])->toBeNull();
});

test('it refuses times that are missing, in the future, reversed or wrong for the state', function (string $state, ?int $start, ?int $finish): void {
    // Arrange
    $run = withLiveItems(liveShowFixture()['run'], [0 => ['state' => $state, 'start' => minutesFromNow(-30), 'finish' => $state === 'finished' ? minutesFromNow(-10) : null]]);

    // Act
    $correct = fn () => resolve(CorrectLiveItemTimes::class)->apply($run, 0, $start === null ? null : now()->addMinutes($start), $finish === null ? null : now()->addMinutes($finish));

    // Assert
    expect($correct)->toThrow(LiveShowException::class, __('live-show.invalid_times'));
})->with([
    'pending item' => ['pending', -30, -10],
    'no start' => ['finished', null, -10],
    'future start' => ['running', 5, null],
    'finished item without a finish' => ['finished', -30, null],
    'running item given a finish' => ['running', -30, -10],
    'finish before start' => ['finished', -10, -30],
    'future finish' => ['finished', -30, 5],
]);

test('it refuses times that overlap the item before or after it', function (bool $earlier): void {
    // Arrange
    $run = withLiveItems(liveShowFixture(segment: true)['run'], [
        0 => ['state' => 'finished', 'start' => minutesFromNow(-60), 'finish' => minutesFromNow(-40)],
        1 => ['state' => 'finished', 'start' => minutesFromNow(-30), 'finish' => minutesFromNow(-10)],
    ]);

    // Act
    $correct = fn () => $earlier
        ? resolve(CorrectLiveItemTimes::class)->apply($run, 1, now()->subMinutes(50), now()->subMinutes(10))
        : resolve(CorrectLiveItemTimes::class)->apply($run, 0, now()->subMinutes(60), now()->subMinutes(20));

    // Assert
    expect($correct)->toThrow(LiveShowException::class, __('live-show.invalid_times'));
})->with(['overlaps the earlier item' => true, 'overlaps the later item' => false]);
