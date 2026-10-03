<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Models\Events\Event;

/**
 * Restore the event and record its statements, so the slot lock upsert shows where it sits among the row locks.
 *
 * @return array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>
 */
function recordRestoreWithSlotLock(Event $event): array
{
    resolve(DeleteAction::class)->handle($event);
    $deletedEvent = Event::withTrashed()->findOrFail($event->id);

    return recordStatements(fn () => resolve(RestoreAction::class)->handle($deletedEvent));
}

/**
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>
 */
function restoreSlotLocks(array $statements): array
{
    return array_filter($statements, fn (array $statement): bool => str_contains($statement['sql'], 'scheduling_slot_locks'));
}

describe('event restore slot locking', function (): void {
    test('it takes the date slot lock before the first event row lock', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);

        // Act
        $statements = recordRestoreWithSlotLock($event);

        // Assert
        $slotLocks = array_keys(restoreSlotLocks($statements));
        $firstRowLock = array_find_key($statements, fn (array $statement): bool => $statement['locked']) ?? -1;

        expect($slotLocks)->toHaveCount(1)
            ->and($slotLocks[0])->toBeLessThan($firstRowLock);
    });

    test('it takes the lock of the exact event date', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);

        // Act
        $statements = recordRestoreWithSlotLock($event);

        // Assert
        $slotLock = array_first(restoreSlotLocks($statements));

        expect($slotLock['bindings'][0] ?? null)->toBe(now()->addWeek()->getTimestamp());
    });

    test('it takes no slot lock for an unscheduled event', function (): void {
        // Arrange
        $event = Event::factory()->unscheduled()->create();

        // Act
        $statements = recordRestoreWithSlotLock($event);

        // Assert
        expect(restoreSlotLocks($statements))->toBeEmpty();
    });
});
