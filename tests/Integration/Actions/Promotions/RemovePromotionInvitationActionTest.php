<?php

declare(strict_types=1);

use App\Actions\Promotions\RemovePromotionInvitationAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it deletes only the pending invitation of that promotion', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Invited]);
    $otherPromotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Invited]);

    // Act
    $removed = app(RemovePromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($removed)->toBeTrue()
        ->and($promotion->memberships()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($otherPromotion->memberships()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('it never removes a membership that is already active or suspended', function (MembershipStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => $status]);

    // Act
    $removed = app(RemovePromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($removed)->toBeFalse()
        ->and($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->status)->toBe($status);
})->with([
    'active membership' => MembershipStatus::Active,
    'suspended membership' => MembershipStatus::Suspended,
]);

test('it reports false when there is nothing to remove', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    // Act
    $removed = app(RemovePromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($removed)->toBeFalse();
});
