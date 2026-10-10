<?php

declare(strict_types=1);

use App\Enums\Naming\GuardedName;
use App\Lifecycle\Naming\RecordNameLock;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;

/**
 * Lock the given title name on a connection of the given driver that only records its statements, so the SQL each
 * engine receives can be checked without that engine's server.
 *
 * @return array<int, array{query: string, bindings: array<int, mixed>}>
 */
function pretendRecordNameLock(string $driver, string $value): array
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
        $connection->pretend(fn (Connection $pretended) => new RecordNameLock($pretended)->lock(GuardedName::TitleName, 7, $value)),
    );
}

describe('record name lock', function (): void {
    test('it locks a name with an upsert that row-locks the name key on every engine', function (string $driver, string $sql): void {
        // Arrange
        $nameKey = resolve(RecordNameLock::class)->key(GuardedName::TitleName, 7, 'World Title');

        // Act
        $statements = pretendRecordNameLock($driver, 'World Title');

        // Assert
        expect($statements)->toBe([['query' => str_replace('KEY', "'{$nameKey}'", $sql), 'bindings' => [$nameKey]]]);
    })->with([
        'mysql' => ['mysql', 'insert into `record_name_locks` (`name_key`) values (KEY) on duplicate key update `name_key` = values(`name_key`)'],
        'pgsql' => ['pgsql', 'insert into "record_name_locks" ("name_key") values (KEY) on conflict ("name_key") do update set "name_key" = "excluded"."name_key"'],
        'sqlite' => ['sqlite', 'insert into "record_name_locks" ("name_key") values (KEY) on conflict ("name_key") do update set "name_key" = "excluded"."name_key"'],
    ]);

    test('it gives case, accent and surrounding space variants of a value one key', function (string $variant): void {
        // Arrange
        $lock = resolve(RecordNameLock::class);

        // Act
        $key = $lock->key(GuardedName::TagTeamName, 3, $variant);

        // Assert
        expect($key)->toBe($lock->key(GuardedName::TagTeamName, 3, 'Cafe Royale'));
    })->with([
        'lower case' => ['cafe royale'],
        'upper case' => ['CAFE ROYALE'],
        'accent' => ['Café Royale'],
        'surrounding space' => ['  Cafe Royale '],
    ]);

    test('it gives a different kind, promotion or value a different key', function (): void {
        // Arrange
        $lock = resolve(RecordNameLock::class);

        // Act
        $keys = [
            $lock->key(GuardedName::TagTeamName, 3, 'Cafe Royale'),
            $lock->key(GuardedName::TagTeamSignatureMove, 3, 'Cafe Royale'),
            $lock->key(GuardedName::TitleName, 3, 'Cafe Royale'),
            $lock->key(GuardedName::TagTeamName, 4, 'Cafe Royale'),
            $lock->key(GuardedName::TagTeamName, null, 'Cafe Royale'),
            $lock->key(GuardedName::TagTeamName, 3, 'Cafe Royal'),
        ];

        // Assert
        expect(array_unique($keys))->toHaveCount(6);
    });

    test('it keeps one lock row per key however often it is locked', function (): void {
        // Arrange
        $lock = resolve(RecordNameLock::class);

        // Act
        $lock->lock(GuardedName::TitleName, null, 'Café Royale');
        $lock->lock(GuardedName::TitleName, null, '  cafe royale');
        $lock->lock(GuardedName::TitleName, 1, 'Cafe Royale');

        // Assert
        expect(DB::table('record_name_locks')->pluck('name_key')->all())
            ->toEqualCanonicalizing([
                $lock->key(GuardedName::TitleName, null, 'Cafe Royale'),
                $lock->key(GuardedName::TitleName, 1, 'Cafe Royale'),
            ]);
    });
});
