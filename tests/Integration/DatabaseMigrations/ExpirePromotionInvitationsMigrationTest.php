<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const EXPIRE_INVITATIONS_MIGRATION = 'migrations/2026_10_06_135303_add_expires_at_to_promotion_invitations_table.php';

function dropInvitationExpiry(): void
{
    Schema::table('promotion_invitations', function (Blueprint $table): void {
        $table->dropIndex(['expires_at']);
    });
    Schema::dropColumns('promotion_invitations', 'expires_at');
}

function runExpireInvitationsMigration(): void
{
    $migration = require database_path(EXPIRE_INVITATIONS_MIGRATION);
    $migration->up();
}

test('it gives existing invitations thirty days from the day they were created', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    dropInvitationExpiry();
    DB::table('promotion_invitations')->insert([
        ['promotion_id' => $promotion->id, 'email' => 'old@example.test', 'role' => 'member', 'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-02 10:00:00'],
        ['promotion_id' => $promotion->id, 'email' => 'undated@example.test', 'role' => 'member', 'created_at' => null, 'updated_at' => null],
    ]);

    // Act
    runExpireInvitationsMigration();

    // Assert
    $expiries = DB::table('promotion_invitations')->orderBy('email')->pluck('expires_at', 'email');

    expect($expiries['old@example.test'])->toBe('2026-01-31 10:00:00')
        ->and($expiries['undated@example.test'])->toBe(now()->addDays(30)->toDateTimeString());
});

test('it keeps the unique key, the email index and the promotion foreign key', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    dropInvitationExpiry();

    // Act
    runExpireInvitationsMigration();

    // Assert
    $indexes = collect(Schema::getIndexes('promotion_invitations'));
    $foreignKeys = collect(Schema::getForeignKeys('promotion_invitations'));
    $column = collect(Schema::getColumns('promotion_invitations'))->firstWhere('name', 'expires_at');

    expect($indexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['promotion_id', 'email']))->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['columns'] === ['email']))->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['columns'] === ['expires_at']))->toBeTrue()
        ->and($foreignKeys->contains(fn (array $key): bool => $key['columns'] === ['promotion_id'] && $key['foreign_table'] === 'promotions' && $key['on_delete'] === 'cascade'))->toBeTrue()
        ->and($column['nullable'])->toBeFalse()
        ->and($promotion->exists)->toBeTrue();
});
