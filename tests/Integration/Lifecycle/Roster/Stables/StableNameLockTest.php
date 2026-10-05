<?php

declare(strict_types=1);

use App\Lifecycle\Roster\Stables\StableNameLock;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;

/**
 * Lock the given name on a connection of the given driver that only records its statements, so the SQL each
 * engine receives can be checked without that engine's server.
 *
 * @return array<int, array{query: string, bindings: array<int, mixed>}>
 */
function pretendStableNameLock(string $driver, string $name): array
{
    // Pretending runs no statement, but inlining the string key into the recorded SQL quotes it through a PDO, so
    // the connection borrows the already open test connection's one instead of reaching for a server.
    $noServer = fn (): PDO => DB::connection()->getPdo();

    $connection = match ($driver) {
        'mysql' => new MySqlConnection($noServer, 'ringside'),
        'pgsql' => new PostgresConnection($noServer, 'ringside'),
        'sqlite' => new SQLiteConnection($noServer, 'ringside'),
        default => throw new LogicException("Unexpected driver {$driver}."),
    };

    return array_map(
        fn (array $statement): array => ['query' => $statement['query'], 'bindings' => $statement['bindings']],
        $connection->pretend(fn (Connection $pretended) => new StableNameLock($pretended)->lock($name)),
    );
}

describe('stable name lock', function (): void {
    test('it locks a name with an upsert that row-locks the name key on every engine', function (string $driver, string $sql): void {
        // Arrange
        $nameKey = hash('sha256', 'the four horsemen');

        // Act
        $statements = pretendStableNameLock($driver, 'The Four Horsemen');

        // Assert
        expect($statements)->toBe([['query' => str_replace('KEY', "'{$nameKey}'", $sql), 'bindings' => [$nameKey]]]);
    })->with([
        'mysql' => ['mysql', 'insert into `stable_name_locks` (`name_key`) values (KEY) on duplicate key update `name_key` = values(`name_key`)'],
        'pgsql' => ['pgsql', 'insert into "stable_name_locks" ("name_key") values (KEY) on conflict ("name_key") do update set "name_key" = "excluded"."name_key"'],
        'sqlite' => ['sqlite', 'insert into "stable_name_locks" ("name_key") values (KEY) on conflict ("name_key") do update set "name_key" = "excluded"."name_key"'],
    ]);

    test('it gives case, accent and surrounding space variants of a name one key', function (string $variant): void {
        // Arrange
        $lock = resolve(StableNameLock::class);

        // Act
        $key = $lock->key($variant);

        // Assert
        expect($key)->toBe($lock->key('Cafe Royale'));
    })->with([
        'lower case' => ['cafe royale'],
        'upper case' => ['CAFE ROYALE'],
        'accent' => ['Café Royale'],
        'accent and case' => ['CAFÉ royale'],
        'surrounding space' => ['  Cafe Royale '],
    ]);

    test('it gives different names different keys', function (): void {
        // Arrange
        $lock = resolve(StableNameLock::class);

        // Act
        $keys = [$lock->key('Cafe Royale'), $lock->key('Cafe Royal'), $lock->key('Royale Cafe')];

        // Assert
        expect(array_unique($keys))->toHaveCount(3);
    });

    test('it keeps one lock row per name however often the name is locked', function (): void {
        // Arrange
        $lock = resolve(StableNameLock::class);

        // Act
        $lock->lock('Café Royale');
        $lock->lock('  cafe royale');
        $lock->lock('Another Name');

        // Assert
        expect(DB::table('stable_name_locks')->pluck('name_key')->all())
            ->toEqualCanonicalizing([$lock->key('Cafe Royale'), $lock->key('Another Name')]);
    });
});
