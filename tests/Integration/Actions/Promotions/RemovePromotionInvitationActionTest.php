<?php

declare(strict_types=1);

use App\Actions\Promotions\RemovePromotionInvitationAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;

test('it deletes only the pending invitation for that promotion and email', function (string $email) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('target@example.test')->create();
    $otherEmail = PromotionInvitation::factory()->for($promotion)->forEmail('bystander@example.test')->create();
    $otherPromotionInvitation = PromotionInvitation::factory()->for($otherPromotion)->forEmail('target@example.test')->create();

    // Act
    $removed = app(RemovePromotionInvitationAction::class)->handle($promotion, $email);

    // Assert
    expect($removed)->toBeTrue()
        ->and($promotion->invitations()->pluck('id')->all())->toBe([$otherEmail->id])
        ->and($otherPromotionInvitation->fresh())->not->toBeNull();
})->with([
    'same email' => ['target@example.test'],
    'other case and whitespace' => ['  Target@Example.TEST '],
]);

test('it never removes a membership', function (MembershipStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => $status]);

    // Act
    $removed = app(RemovePromotionInvitationAction::class)->handle($promotion, 'member@example.test');

    // Assert
    expect($removed)->toBeFalse()
        ->and($promotion->memberships()->sole()->status)->toBe($status);
})->with([
    'active membership' => MembershipStatus::Active,
    'suspended membership' => MembershipStatus::Suspended,
]);

test('it reports false when there is nothing to remove', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $removed = app(RemovePromotionInvitationAction::class)->handle($promotion, 'nobody@example.test');

    // Assert
    expect($removed)->toBeFalse();
});

test('it forgets the memoised invitations of the user', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'memo@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('memo@example.test')->create();
    $context = app(PromotionContextService::class);
    $before = $context->pendingInvitationsFor($user);

    // Act
    app(RemovePromotionInvitationAction::class)->handle($promotion, 'memo@example.test');

    // Assert
    expect($before)->toHaveCount(1)
        ->and($context->pendingInvitationsFor($user))->toBeEmpty();
});

test('it locks the promotion before deleting', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('lock@example.test')->create();

    // Act
    $statements = recordStatements(fn () => app(RemovePromotionInvitationAction::class)->handle($promotion, 'lock@example.test'));

    // Assert
    $lock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "promotions"'));
    $delete = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'delete from "promotion_invitations"'));

    expect($lock)->toBeLessThan($delete);
});
