<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;

/*
 * Local development runs on SQLite, where the web server, queue and scheduler can write at the same time. The sqlite
 * connection in config/database.php uses WAL (reads continue during a write), a busy timeout (wait instead of failing
 * with "database is locked"), NORMAL sync and IMMEDIATE transactions (take the write lock at BEGIN instead of failing
 * when a read transaction later tries to write). The in-memory test database cannot use WAL, so these tests open the
 * configured connection on a temporary file.
 */

/**
 * Open the configured sqlite connection on a temporary database file.
 *
 * @return array{connection: SQLiteConnection, path: string}
 */
function sqliteFileConnection(): array
{
    $path = tempnam(sys_get_temp_dir(), 'ringside-sqlite-');

    config(['database.connections.sqlite_file_probe' => [
        ...config()->array('database.connections.sqlite'),
        'database' => $path,
    ]]);

    $connection = DB::connection('sqlite_file_probe');

    if (! $connection instanceof SQLiteConnection) {
        throw new LogicException('The sqlite connection must use the sqlite driver.');
    }

    return ['connection' => $connection, 'path' => $path];
}

/**
 * Open a second, plain connection to the same file that gives up at once instead of waiting for a lock.
 */
function otherSqliteWriter(string $path): SQLiteConnection
{
    config(['database.connections.sqlite_other_writer' => [
        'driver' => 'sqlite',
        'database' => $path,
        'prefix' => '',
        'busy_timeout' => 0,
    ]]);

    $connection = DB::connection('sqlite_other_writer');

    if (! $connection instanceof SQLiteConnection) {
        throw new LogicException('The other writer must use the sqlite driver.');
    }

    return $connection;
}

function removeSqliteFile(string $path): void
{
    DB::purge('sqlite_file_probe');
    DB::purge('sqlite_other_writer');

    foreach ([$path, "{$path}-wal", "{$path}-shm"] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

test('the sqlite connection uses wal, a busy timeout and normal sync', function () {
    // Arrange
    ['connection' => $connection, 'path' => $path] = sqliteFileConnection();

    // Act
    $journalMode = $connection->scalar('pragma journal_mode');
    $busyTimeout = $connection->scalar('pragma busy_timeout');
    $synchronous = $connection->scalar('pragma synchronous');

    // Assert
    expect($journalMode)->toBe('wal')
        ->and($busyTimeout)->toBe(5000)
        ->and($synchronous)->toBe(1);

    removeSqliteFile($path);
});

test('sqlite transactions take the write lock when they begin', function () {
    // Arrange
    ['connection' => $connection, 'path' => $path] = sqliteFileConnection();
    $otherWriter = otherSqliteWriter($path);
    $otherWriterBlocked = false;

    // Act
    $connection->beginTransaction();

    try {
        $otherWriter->statement('begin immediate');
        $otherWriter->statement('rollback');
    } catch (QueryException) {
        $otherWriterBlocked = true;
    }

    $connection->rollBack();

    // Assert
    expect($otherWriterBlocked)->toBeTrue();

    removeSqliteFile($path);
});
