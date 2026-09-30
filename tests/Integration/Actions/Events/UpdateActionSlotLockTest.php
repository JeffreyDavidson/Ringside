<?php

declare(strict_types=1);

use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Matches\SchedulingSlotLockService;
use Illuminate\Support\Facades\DB;

/**
 * Run the update against a slot lock that believes it is on PostgreSQL. Every advisory lock it would take
 * is issued as a marker statement, so the recorded statements show where it sits among the row locks.
 *
 * @return array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>
 */
function recordUpdateWithSlotLock(Event $event, EventData $data): array
{
    app()->instance(SchedulingSlotLockService::class, postgresSlotLock(
        fn (string $sql, array $bindings): array => DB::select('select ? as slot_lock_key', [$bindings[1]]),
    ));

    return recordStatements(fn () => resolve(UpdateAction::class)->handle($event, $data));
}

/**
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return array<int, int>
 */
function slotLockPositions(array $statements): array
{
    return array_keys(array_filter($statements, fn (array $statement): bool => str_contains($statement['sql'], 'slot_lock_key')));
}

describe('event reschedule slot locking', function (): void {
    test('it takes the old and new slot locks before the first event row lock', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeeks(2)]);
        Event::factory()->create(['date' => now()->addWeek()]);
        $data = new EventData($event->name, now()->addWeek(), null, null);

        // Act
        $statements = recordUpdateWithSlotLock($event, $data);

        // Assert
        $firstRowLock = array_find_key($statements, fn (array $statement): bool => $statement['locked']);

        expect(slotLockPositions($statements))->toHaveCount(2)
            ->and(collect(slotLockPositions($statements))->every(fn (int $position): bool => $position < $firstRowLock))->toBeTrue();
    });

    test('it takes the slot locks in ascending time order whichever way the event moves', function (int $oldWeeks, int $newWeeks): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeeks($oldWeeks)]);
        $data = new EventData($event->name, now()->addWeeks($newWeeks), null, null);

        // Act
        $statements = recordUpdateWithSlotLock($event, $data);

        // Assert
        $keys = array_map(
            fn (int $position): mixed => $statements[$position]['bindings'][0],
            slotLockPositions($statements),
        );
        $expectedKeys = collect([$oldWeeks, $newWeeks])
            ->sort()
            ->map(fn (int $weeks): int => (int) crc32((string) now()->addWeeks($weeks)->getTimestamp()))
            ->map(fn (int $hash): int => $hash >= 0x80000000 ? $hash - 0x100000000 : $hash)
            ->values()
            ->all();

        expect($keys)->toBe($expectedKeys);
    })->with([
        'moved later' => [1, 3],
        'moved earlier' => [3, 1],
    ]);

    test('it takes only the new slot lock when scheduling an unscheduled event', function (): void {
        // Arrange
        $event = Event::factory()->unscheduled()->create();
        $data = new EventData($event->name, now()->addWeek(), null, null);

        // Act
        $statements = recordUpdateWithSlotLock($event, $data);

        // Assert
        expect(slotLockPositions($statements))->toHaveCount(1);
    });

    test('it takes only the old slot lock when unscheduling an event', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);
        $data = new EventData($event->name, null, null, null);

        // Act
        $statements = recordUpdateWithSlotLock($event, $data);

        // Assert
        expect(slotLockPositions($statements))->toHaveCount(1);
    });

    test('it takes no slot lock when the date does not change', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);
        $data = new EventData('Renamed Event', now()->addWeek(), null, null);

        // Act
        $statements = recordUpdateWithSlotLock($event, $data);

        // Assert
        expect(slotLockPositions($statements))->toBeEmpty()
            ->and($event->refresh()->name)->toBe('Renamed Event');
    });

    test('it keeps rejecting a conflicting reschedule after taking the slot locks', function (): void {
        // Arrange
        $event = Event::factory()->create(['date' => now()->addWeek()]);
        $otherEvent = Event::factory()->create(['date' => now()->addWeeks(2)]);
        $wrestler = Wrestler::factory()->bookable()->create();
        EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();
        EventMatch::factory()->forEvent($otherEvent)->withCompetitors([$wrestler])->create();
        $data = new EventData($event->name, now()->addWeeks(2), null, null);

        // Act
        $act = fn () => recordUpdateWithSlotLock($event, $data);

        // Assert
        expect($act)->toThrow(SchedulingConflictException::class)
            ->and($event->refresh()->date?->toDateTimeString())->toBe(now()->addWeek()->toDateTimeString());
    });
});
