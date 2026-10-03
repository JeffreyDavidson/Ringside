<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
 * The scheduling and lifecycle locks assume READ COMMITTED: once a lock is granted, every following statement sees
 * the rows other transactions committed while it waited. PostgreSQL defaults to it; MySQL defaults to REPEATABLE
 * READ, so config/database.php sets it for MySQL connections, together with a UTC session time zone.
 */

test('mysql connections run at read committed in utc', function () {
    // Act
    $isolation = DB::scalar('select @@transaction_isolation');
    $timeZone = DB::scalar('select @@session.time_zone');

    // Assert
    expect($isolation)->toBe('READ-COMMITTED')
        ->and($timeZone)->toBe('+00:00');
})->skip(fn (): bool => ! runsOnDriver('mysql'), 'Only MySQL connections set an isolation level.');

test('postgresql connections run at read committed', function () {
    // Act
    $isolation = DB::scalar('show transaction_isolation');

    // Assert
    expect($isolation)->toBe('read committed');
})->skip(fn (): bool => ! runsOnDriver('pgsql'), 'Only PostgreSQL reports this isolation level; SQLite serializes writers instead.');
