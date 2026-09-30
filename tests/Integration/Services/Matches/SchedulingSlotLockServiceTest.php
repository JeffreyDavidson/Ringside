<?php

declare(strict_types=1);

use App\Services\Matches\SchedulingSlotLockService;
use Illuminate\Support\Carbon;

describe('scheduling slot lock service', function (): void {
    test('it takes one transaction scoped advisory lock per slot on postgres', function (): void {
        // Arrange
        $statements = new ArrayObject;
        $lock = postgresSlotLock(fn (string $sql, array $bindings) => $statements->append(['sql' => $sql, 'bindings' => $bindings]));

        // Act
        $lock->lock(Carbon::createFromTimestamp(1_000_000_002));

        // Assert
        expect($statements->getArrayCopy())->toBe([
            ['sql' => 'select pg_advisory_xact_lock(?, ?)', 'bindings' => [0x534C4F54, 28_209_892]],
        ]);
    });

    test('it derives a stable key that differs per slot and fits a signed integer', function (): void {
        // Arrange
        $statements = new ArrayObject;
        $lock = postgresSlotLock(fn (string $sql, array $bindings) => $statements->append(['sql' => $sql, 'bindings' => $bindings]));

        // Act
        $lock->lock(Carbon::createFromTimestamp(1_000_000_000));
        $lock->lock(Carbon::createFromTimestamp(1_000_000_000));
        $lock->lock(Carbon::createFromTimestamp(1_000_000_002));

        // Assert
        expect(array_column($statements->getArrayCopy(), 'bindings'))->toBe([
            [0x534C4F54, -274_721_848],
            [0x534C4F54, -274_721_848],
            [0x534C4F54, 28_209_892],
        ]);
    });

    test('it acquires several slots in ascending timestamp order regardless of argument order', function (): void {
        // Arrange
        $statements = new ArrayObject;
        $lock = postgresSlotLock(fn (string $sql, array $bindings) => $statements->append(['sql' => $sql, 'bindings' => $bindings]));
        $earlier = Carbon::createFromTimestamp(1_000_000_000);
        $later = Carbon::createFromTimestamp(1_000_000_002);

        // Act
        $lock->lock($later, $earlier);
        $lock->lock($earlier, $later);

        // Assert
        expect(array_column($statements->getArrayCopy(), 'bindings'))->toBe([
            [0x534C4F54, -274_721_848],
            [0x534C4F54, 28_209_892],
            [0x534C4F54, -274_721_848],
            [0x534C4F54, 28_209_892],
        ]);
    });

    test('it locks a slot only once when both dates are the same instant and skips null dates', function (): void {
        // Arrange
        $statements = new ArrayObject;
        $lock = postgresSlotLock(fn (string $sql, array $bindings) => $statements->append(['sql' => $sql, 'bindings' => $bindings]));
        $slot = Carbon::createFromTimestamp(1_000_000_002);

        // Act
        $lock->lock($slot, null, $slot->copy());
        $lock->lock(null, null);

        // Assert
        expect($statements)->toHaveCount(1);
    });

    test('it takes no lock on sqlite because sqlite serializes writers', function (): void {
        // Arrange
        $connection = driverConnection('sqlite');
        $connection->expects('select')->never();
        $lock = new SchedulingSlotLockService($connection);

        // Act
        $lock->lock(Carbon::createFromTimestamp(1_000_000_002));

        // Assert
        $connection->verify();
    });

    test('it rejects a driver without a slot locking strategy', function (): void {
        // Arrange
        $connection = driverConnection('mysql');
        $connection->expects('select')->never();
        $lock = new SchedulingSlotLockService($connection);

        // Act
        $act = fn () => $lock->lock(Carbon::createFromTimestamp(1_000_000_002));

        // Assert
        expect($act)->toThrow(LogicException::class, 'The database driver does not support scheduling slot locks.');
    });
});
