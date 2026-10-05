<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;

/**
 * Serializes schedule changes into the same date and time slot, even while no event exists there yet.
 *
 * Row locks cannot lock a row that does not exist, so two transactions moving different events into the
 * same empty slot would never see each other's uncommitted move. Every action that moves an event to a
 * date takes this lock first, before any event row lock, and the existing row-locked conflict checks then
 * observe the other transaction's committed move.
 *
 * The lock is a row of the scheduling_slot_locks table, so it works the same way on MySQL, PostgreSQL and
 * SQLite. The rows only coordinate transactions; they hold no domain data and nothing reads them.
 */
final readonly class SchedulingSlotLock
{
    private const string TABLE = 'scheduling_slot_locks';

    public function __construct(#[DB] private Connection $connection) {}

    /**
     * Lock the slot of every given date, in ascending timestamp order so that transactions locking the
     * same slots always queue in the same order and can never deadlock on each other. Null dates and
     * repeated dates take nothing extra.
     *
     * Each slot is one upsert of its row, keyed by the exact unix timestamp. The upsert holds an exclusive lock
     * on that row until the surrounding transaction commits or rolls back, so it must be called inside the
     * transaction it should live for: MySQL's ON DUPLICATE KEY UPDATE takes an exclusive record lock on an
     * existing key (never the shared lock a plain insert takes, which lets two waiters deadlock), PostgreSQL's
     * ON CONFLICT DO UPDATE locks the conflicting row, and a concurrent insert of the same new key waits for the
     * first transaction on both. SQLite serializes all writers, so the row lock there is the write itself.
     */
    public function lock(?Carbon ...$slots): void
    {
        $timestamps = collect($slots)
            ->filter()
            ->map(fn (Carbon $slot): int => $slot->getTimestamp())
            ->unique()
            ->sort();

        foreach ($timestamps as $timestamp) {
            $this->connection->table(self::TABLE)->upsert(['slot' => $timestamp], ['slot'], ['slot']);
        }
    }
}
