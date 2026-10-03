<?php

declare(strict_types=1);

use App\Services\Matches\SchedulingSlotLockService;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lock the given slots on a connection of the given driver that only records its statements, so the SQL each
 * engine receives can be checked without that engine's server.
 *
 * @return array<int, array{query: string, bindings: array<int, mixed>}>
 */
function pretendSlotLock(string $driver, ?Carbon ...$slots): array
{
    $noServer = fn (): never => throw new LogicException('The pretended connection never opens a connection.');

    $connection = match ($driver) {
        'mysql' => new MySqlConnection($noServer, 'ringside'),
        'pgsql' => new PostgresConnection($noServer, 'ringside'),
        'sqlite' => new SQLiteConnection($noServer, 'ringside'),
        default => throw new LogicException("Unexpected driver {$driver}."),
    };

    return array_map(
        fn (array $statement): array => ['query' => $statement['query'], 'bindings' => $statement['bindings']],
        $connection->pretend(fn (Connection $pretended) => new SchedulingSlotLockService($pretended)->lock(...$slots)),
    );
}

describe('scheduling slot lock service', function (): void {
    test('it locks a slot with an upsert that row-locks the slot on every engine', function (string $driver, string $sql): void {
        // Arrange
        $slot = Carbon::createFromTimestamp(1_000_000_002);

        // Act
        $statements = pretendSlotLock($driver, $slot);

        // Assert
        expect($statements)->toBe([['query' => $sql, 'bindings' => [1_000_000_002]]]);
    })->with([
        'mysql' => ['mysql', 'insert into `scheduling_slot_locks` (`slot`) values (1000000002) on duplicate key update `slot` = values(`slot`)'],
        'pgsql' => ['pgsql', 'insert into "scheduling_slot_locks" ("slot") values (1000000002) on conflict ("slot") do update set "slot" = "excluded"."slot"'],
        'sqlite' => ['sqlite', 'insert into "scheduling_slot_locks" ("slot") values (1000000002) on conflict ("slot") do update set "slot" = "excluded"."slot"'],
    ]);

    test('it acquires several slots in ascending timestamp order regardless of argument order', function (): void {
        // Arrange
        $earlier = Carbon::createFromTimestamp(1_000_000_000);
        $later = Carbon::createFromTimestamp(1_000_000_002);

        // Act
        $laterFirst = pretendSlotLock('mysql', $later, $earlier);
        $earlierFirst = pretendSlotLock('mysql', $earlier, $later);

        // Assert
        expect(array_column($laterFirst, 'bindings'))->toBe([[1_000_000_000], [1_000_000_002]])
            ->and(array_column($earlierFirst, 'bindings'))->toBe([[1_000_000_000], [1_000_000_002]]);
    });

    test('it locks a slot only once when both dates are the same instant and skips null dates', function (): void {
        // Arrange
        $slot = Carbon::createFromTimestamp(1_000_000_002);

        // Act
        $repeated = pretendSlotLock('mysql', $slot, null, $slot->copy());
        $nothing = pretendSlotLock('mysql', null, null);

        // Assert
        expect($repeated)->toHaveCount(1)
            ->and($nothing)->toBeEmpty();
    });

    test('it keeps one lock row per slot however often the slot is locked', function (): void {
        // Arrange
        $lock = resolve(SchedulingSlotLockService::class);
        $earlier = Carbon::createFromTimestamp(1_000_000_000);
        $later = Carbon::createFromTimestamp(1_000_000_002);

        // Act
        $lock->lock($later, $earlier);
        $lock->lock($earlier);

        // Assert
        expect(DB::table('scheduling_slot_locks')->orderBy('slot')->pluck('slot')->all())
            ->toEqual([1_000_000_000, 1_000_000_002]);
    });
});
