<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Models\Events\Event;
use App\Services\Matches\SchedulingSlotLockService;
use Illuminate\Support\Facades\DB;

/**
 * Restore the event against a slot lock that believes it is on PostgreSQL. Every advisory lock it would take
 * is issued as a marker statement, so the recorded statements show where it sits among the row locks.
 *
 * @return array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>
 */
function recordRestoreWithSlotLock(Event $event): array
{
    resolve(DeleteAction::class)->handle($event);
    $deletedEvent = Event::withTrashed()->findOrFail($event->id);

    app()->instance(SchedulingSlotLockService::class, postgresSlotLock(
        fn (string $sql, array $bindings): array => DB::select('select ? as slot_lock_key', [$bindings[1]]),
    ));

    return recordStatements(fn () => resolve(RestoreAction::class)->handle($deletedEvent));
}

describe('event restore slot locking', function (): void {
    test('it takes the date slot lock before the first event row lock', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);

        // Act
        $statements = recordRestoreWithSlotLock($event);

        // Assert
        $slotLocks = array_keys(array_filter($statements, fn (array $statement): bool => str_contains($statement['sql'], 'slot_lock_key')));
        $firstRowLock = array_find_key($statements, fn (array $statement): bool => $statement['locked']) ?? -1;

        expect($slotLocks)->toHaveCount(1)
            ->and($slotLocks[0])->toBeLessThan($firstRowLock);
    });

    test('it takes the lock of the exact event date', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);
        $expectedKey = (int) crc32((string) now()->addWeek()->getTimestamp());

        // Act
        $statements = recordRestoreWithSlotLock($event);

        // Assert
        $slotLock = array_find($statements, fn (array $statement): bool => str_contains($statement['sql'], 'slot_lock_key'));

        expect($slotLock['bindings'][0] ?? null)->toBe($expectedKey >= 0x80000000 ? $expectedKey - 0x100000000 : $expectedKey);
    });

    test('it takes no slot lock for an unscheduled event', function (): void {
        // Arrange
        $event = Event::factory()->unscheduled()->create();

        // Act
        $statements = recordRestoreWithSlotLock($event);

        // Assert
        expect(array_filter($statements, fn (array $statement): bool => str_contains($statement['sql'], 'slot_lock_key')))->toBeEmpty();
    });
});
