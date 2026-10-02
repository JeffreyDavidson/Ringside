<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        $this->ensureNoEmailDiffersOnlyByCase();

        // An expression index is created with plain DDL on SQLite and PostgreSQL, so no table is rebuilt
        // and the existing case-sensitive users_email_unique index is left in place. MySQL and MariaDB
        // compare emails case-insensitively through the column collation already.
        match ($connection->getDriverName()) {
            'sqlite', 'pgsql' => $connection->statement(
                'CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))'
            ),
            'mysql', 'mariadb' => null,
            default => throw new LogicException('The database driver does not support case-insensitive unique user emails.'),
        };
    }

    /**
     * Abort before any schema change when existing data would violate the new index.
     *
     * Existing emails are never rewritten: the operator decides which duplicate accounts to keep.
     */
    private function ensureNoEmailDiffersOnlyByCase(): void
    {
        $conflicts = DB::table('users')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->groupBy(fn (object $user): string => mb_strtolower($user->email))
            ->filter(fn ($users): bool => $users->count() > 1)
            ->map(fn ($users, string $email): string => sprintf(
                'email %s is used by user ids %s',
                $email,
                $users->pluck('id')->implode(', '),
            ))
            ->implode('; ');

        if ($conflicts === '') {
            return;
        }

        throw new RuntimeException(
            "Cannot enforce case-insensitive unique user emails: {$conflicts}. "
            .'No changes were made. Change or delete the duplicate users so each email is unique '
            .'ignoring case, then re-run "php artisan migrate".'
        );
    }
};
