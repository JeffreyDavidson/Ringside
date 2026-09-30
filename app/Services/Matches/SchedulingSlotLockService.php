<?php

declare(strict_types=1);

namespace App\Services\Matches;

use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Serializes schedule changes into the same date and time slot, even while no event exists there yet.
 *
 * Row locks cannot lock a row that does not exist, so two transactions moving different events into the
 * same empty slot would never see each other's uncommitted move. Every action that moves an event to a
 * date takes this lock first, before any event row lock, and the existing row-locked conflict checks then
 * observe the other transaction's committed move.
 *
 * The lock only coordinates transactions; it reads and writes no domain data.
 */
final readonly class SchedulingSlotLockService
{
    /**
     * First key of the two-integer advisory lock form (ASCII "SLOT"). PostgreSQL keeps the two-integer
     * keyspace separate from the single-bigint one, so these locks cannot collide with other advisory locks.
     */
    private const int NAMESPACE_KEY = 0x534C4F54;

    public function __construct(#[DB] private Connection $connection) {}

    /**
     * Lock the slot of every given date, in ascending timestamp order so that transactions locking the
     * same slots always queue in the same order and can never deadlock on each other. Null dates and
     * repeated dates take nothing extra.
     *
     * On PostgreSQL this is a transaction-scoped advisory lock: it is released automatically at commit or
     * rollback and, unlike a session lock, is safe behind PgBouncer transaction pooling. It must be called
     * inside the transaction it should live for. SQLite serializes all writers, so it takes nothing.
     * Any other driver is rejected instead of silently running unprotected.
     *
     * @throws LogicException When the database driver has no slot locking strategy
     */
    public function lock(?Carbon ...$slots): void
    {
        $timestamps = collect($slots)
            ->filter()
            ->map(fn (Carbon $slot): int => $slot->getTimestamp())
            ->unique()
            ->sort()
            ->values();

        foreach ($timestamps as $timestamp) {
            match ($this->connection->getDriverName()) {
                'pgsql' => $this->connection->select('select pg_advisory_xact_lock(?, ?)', [self::NAMESPACE_KEY, $this->keyFor($timestamp)]),
                'sqlite' => null,
                default => throw new LogicException('The database driver does not support scheduling slot locks.'),
            };
        }
    }

    /**
     * The second advisory key: a stable 32-bit hash of the exact slot timestamp, mapped to the signed range
     * PostgreSQL integers use. Two slots may share a key; that only serializes them needlessly.
     */
    private function keyFor(int $timestamp): int
    {
        $hash = crc32((string) $timestamp);

        return $hash >= 0x80000000 ? $hash - 0x100000000 : $hash;
    }
}
