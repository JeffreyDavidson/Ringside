<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * Serializes splits that choose the same name for a new stable without a promotion, even while no stable has that name yet.
 *
 * MySQL has no partial unique index, and its generated-column index treats a null promotion as distinct from every
 * other, so nothing in the database stops two active stables without a promotion from sharing a name there, and a
 * row lock cannot lock a stable that does not exist yet. A split of such a stable takes this lock first, before any
 * stable row lock, and the name check that follows then observes the other transaction's committed stable.
 *
 * The lock is a row of the stable_name_locks table, so it works the same way on MySQL, PostgreSQL and SQLite. The
 * rows only coordinate transactions; they hold no domain data and nothing reads them.
 */
final readonly class StableNameLock
{
    private const string TABLE = 'stable_name_locks';

    public function __construct(#[DB] private Connection $connection) {}

    /**
     * Lock the given stable name until the surrounding transaction commits or rolls back, so it must be called
     * inside the transaction it should live for.
     *
     * The lock is one upsert of the name's row: MySQL's ON DUPLICATE KEY UPDATE takes an exclusive record lock on an
     * existing key (never the shared lock a plain insert takes, which lets two waiters deadlock), PostgreSQL's
     * ON CONFLICT DO UPDATE locks the conflicting row, and a concurrent insert of the same new key waits for the
     * first transaction on both. SQLite serializes all writers, so the row lock there is the write itself.
     */
    public function lock(string $name): void
    {
        $this->connection->table(self::TABLE)->upsert(['name_key' => $this->key($name)], ['name_key'], ['name_key']);
    }

    /**
     * The key every spelling of a name that MySQL's case- and accent-insensitive collation treats as equal shares:
     * trimmed, lower-cased and transliterated to ASCII. Residual: two names that differ only in a way the
     * transliteration does not fold (for example characters it maps differently from the collation) get different
     * keys, and then nothing but the form validation and the name check guards them on MySQL.
     */
    public function key(string $name): string
    {
        return hash('sha256', Str::ascii(mb_strtolower(mb_trim($name))));
    }
}
