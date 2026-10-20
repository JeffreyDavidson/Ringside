<?php

declare(strict_types=1);

use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowStateMachine;
use App\Models\Events\EventLiveRun;

/**
 * An unsaved run in the given status whose items are in the given states.
 *
 * @param  array<array-key, LiveItemStatus>  $states
 */
function liveRunWithItems(LiveShowStatus $status, array $states = []): EventLiveRun
{
    return EventLiveRun::factory()->make([
        'status' => $status,
        'content' => ['items' => array_values(array_map(fn (LiveItemStatus $state): array => ['state' => $state->value], $states))],
    ]);
}

describe('live show state machine', function (): void {
    it('starts a new run as prepared', function (): void {
        // Arrange
        $machine = new LiveShowStateMachine;

        // Act
        $initial = $machine->initial();

        // Assert
        expect($initial)->toBe(LiveShowStatus::Prepared);
    });

    it('lets an open show take more work', function (LiveShowStatus $status): void {
        // Arrange
        $machine = new LiveShowStateMachine;
        $run = liveRunWithItems($status);

        // Act
        $check = fn () => $machine->ensureOpen($run);

        // Assert
        expect($check)->not->toThrow(LiveShowException::class);
    })->with([
        'prepared' => [LiveShowStatus::Prepared],
        'running' => [LiveShowStatus::Running],
    ]);

    it('refuses more work on a completed show', function (): void {
        // Arrange
        $machine = new LiveShowStateMachine;
        $run = liveRunWithItems(LiveShowStatus::Completed);

        // Act
        $check = fn () => $machine->ensureOpen($run);

        // Assert
        expect($check)->toThrow(LiveShowException::class, __('live-show.completed'));
    });

    it('lets a show complete when every item is finished or skipped', function (LiveShowStatus $status, array $states): void {
        // Arrange
        $machine = new LiveShowStateMachine;
        $run = liveRunWithItems($status, $states);

        // Act
        $complete = fn () => $machine->ensureCanComplete($run);

        // Assert
        expect($complete)->not->toThrow(LiveShowException::class);
    })->with([
        'a running show with finished and skipped items' => [LiveShowStatus::Running, [LiveItemStatus::Finished, LiveItemStatus::Skipped]],
        'a prepared show with only skipped items' => [LiveShowStatus::Prepared, [LiveItemStatus::Skipped]],
    ]);

    it('refuses to complete a show with a pending or running item', function (array $states): void {
        // Arrange
        $machine = new LiveShowStateMachine;
        $run = liveRunWithItems(LiveShowStatus::Running, $states);

        // Act
        $complete = fn () => $machine->ensureCanComplete($run);

        // Assert
        expect($complete)->toThrow(LiveShowException::class, __('live-show.unfinished'));
    })->with([
        'a pending item' => [[LiveItemStatus::Finished, LiveItemStatus::Pending]],
        'a running item' => [[LiveItemStatus::Finished, LiveItemStatus::Running]],
    ]);
});
