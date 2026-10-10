<?php

declare(strict_types=1);

namespace App\Lifecycle\Naming;

use App\Enums\Naming\GuardedName;
use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * Serializes saves that choose the same unique value for a record, even while no record has that value yet.
 *
 * Nothing in the database keeps tag team names, tag team signature moves or title names unique on any engine, and a
 * row lock cannot lock a record that does not exist yet, so two concurrent saves would each pass the name check and
 * both commit. A create or update takes this lock first, before any row lock of its record, and the name check that
 * follows then observes the other transaction's committed record.
 *
 * The lock is a row of the record_name_locks table, so it works the same way on MySQL, PostgreSQL and SQLite. The
 * rows only coordinate transactions; they hold no domain data and nothing reads them. The key covers the kind of
 * value, the promotion and the value, so wrestler, event and venue names can be added as further GuardedName cases.
 */
final readonly class RecordNameLock
{
    private const string TABLE = 'record_name_locks';

    public function __construct(#[DB] private Connection $connection) {}

    /**
     * Lock the value until the surrounding transaction commits or rolls back, so it must be called inside the
     * transaction it should live for. When a save guards several values it locks them in one fixed order (name,
     * then signature move), so two saves never wait for each other's second lock.
     *
     * The lock is one upsert of the key's row: MySQL's ON DUPLICATE KEY UPDATE takes an exclusive record lock on an
     * existing key (never the shared lock a plain insert takes, which lets two waiters deadlock), PostgreSQL's
     * ON CONFLICT DO UPDATE locks the conflicting row, and a concurrent insert of the same new key waits for the
     * first transaction on both. SQLite serializes all writers, so the row lock there is the write itself.
     */
    public function lock(GuardedName $kind, ?int $promotionId, string $value): void
    {
        $this->connection->table(self::TABLE)->upsert(['name_key' => $this->key($kind, $promotionId, $value)], ['name_key'], ['name_key']);
    }

    /**
     * The key every spelling of a value that MySQL's case- and accent-insensitive collation treats as equal shares:
     * trimmed, lower-cased and transliterated to ASCII, per kind and promotion (null for a record without one).
     * Residual: two values that differ only in a way the transliteration does not fold get different keys, and then
     * nothing but the form validation and the name check guards them on MySQL.
     */
    public function key(GuardedName $kind, ?int $promotionId, string $value): string
    {
        return hash('sha256', "{$kind->value}|".($promotionId ?? 'none').'|'.Str::ascii(mb_strtolower(mb_trim($value))));
    }
}
