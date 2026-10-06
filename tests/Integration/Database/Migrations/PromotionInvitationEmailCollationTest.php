<?php

declare(strict_types=1);

use App\Actions\Promotions\AcceptPromotionInvitationAction;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the invitation email column compares bytes exactly on MySQL', function () {
    // Arrange
    $table = 'promotion_invitations';

    // Act
    $collation = DB::scalar(
        'select collation_name from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
        [$table, 'email'],
    );

    // Assert
    $indexes = collect(Schema::getIndexes($table));

    expect($collation)->toBe('utf8mb4_bin')
        ->and($indexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['promotion_id', 'email']))->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['columns'] === ['email']))->toBeTrue();
})->skip(fn (): bool => ! runsOnDriver('mysql'), 'Collations only exist on MySQL; SQLite and PostgreSQL already compare strings exactly.');

test('an invitation for a plain email is not visible to or acceptable by a lookalike email', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('jose@corp.com')->create();
    $lookalike = User::factory()->create(['email' => 'josé@corp.com']);

    // Act
    $pending = app(PromotionContextService::class)->pendingInvitationsFor($lookalike);
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $lookalike);

    // Assert
    expect($pending)->toBeEmpty()
        ->and($accepted)->toBeNull()
        ->and($promotion->invitations()->count())->toBe(1);
});
