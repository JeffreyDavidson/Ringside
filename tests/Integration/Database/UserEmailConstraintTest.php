<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const USER_EMAIL_MIGRATION = 'migrations/2026_10_02_090000_enforce_case_insensitive_unique_user_email.php';

function insertRawUser(string $email): void
{
    DB::table('users')->insert([
        'first_name' => 'Raw',
        'last_name' => 'User',
        'email' => $email,
        'password' => 'secret',
        'role' => 'basic',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('the database rejects an email that differs only by case', function () {
    insertRawUser('Foo@Example.com');

    // The nested transaction becomes a savepoint, so PostgreSQL keeps the surrounding
    // test transaction usable after the unique violation.
    expect(fn () => DB::transaction(fn () => insertRawUser('foo@example.com')))
        ->toThrow(QueryException::class);
});

test('the database still accepts different emails', function () {
    insertRawUser('foo@example.com');
    insertRawUser('bar@example.com');

    expect(DB::table('users')->count())->toBe(2);
});

test('emails are trimmed and lowercased when a user is written', function () {
    $created = User::factory()->create(['email' => '  Foo@Example.COM ']);
    $created->update(['email' => 'Bar@Example.com']);

    expect($created->refresh()->email)->toBe('bar@example.com');
});

test('the migration lists the users that share an email ignoring case before changing anything', function () {
    DB::statement('DROP INDEX users_email_lower_unique');
    insertRawUser('Foo@Example.com');
    insertRawUser('foo@example.com');
    $ids = DB::table('users')->orderBy('id')->pluck('id')->implode(', ');
    $migration = require database_path(USER_EMAIL_MIGRATION);
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "Cannot enforce case-insensitive unique user emails: email foo@example.com is used by user ids {$ids}. No changes were made."
        )
        ->and(collect(Schema::getIndexes('users'))->contains('name', 'users_email_lower_unique'))->toBeFalse()
        ->and(DB::table('users')->pluck('email')->all())->toBe(['Foo@Example.com', 'foo@example.com']);
})->skip(fn (): bool => runsOnDriver('mysql'), 'MySQL has no lower(email) index to drop and its case-insensitive collation rejects the duplicate itself.');

test('the migration recreates the index when the data is clean', function () {
    DB::statement('DROP INDEX users_email_lower_unique');
    insertRawUser('foo@example.com');
    $migration = require database_path(USER_EMAIL_MIGRATION);

    $migration->up();

    expect(collect(Schema::getIndexes('users'))->contains('name', 'users_email_lower_unique'))->toBeTrue();
})->skip(fn (): bool => runsOnDriver('mysql'), 'MySQL enforces case-insensitive email uniqueness through the column collation, so the migration creates no index there.');
