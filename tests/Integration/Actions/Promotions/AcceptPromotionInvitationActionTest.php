<?php

declare(strict_types=1);

use App\Actions\Promotions\AcceptPromotionInvitationAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it activates the invited users own membership with the role the owner chose', function (MembershipRole $role) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Invited]);
    $otherPromotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Invited]);

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    $membership = $promotion->memberships()->where('user_id', $user->id)->firstOrFail();

    expect($accepted)->toBe($role)
        ->and($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->role)->toBe($role)
        ->and($promotion->hasActiveMember($user))->toBeTrue()
        ->and($otherPromotion->hasActiveMember($user))->toBeFalse();
})->with([
    'owner' => MembershipRole::Owner,
    'manager' => MembershipRole::Manager,
    'member' => MembershipRole::Member,
]);

test('it does nothing when the user has no pending invitation', function (?MembershipStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    if ($status instanceof MembershipStatus) {
        $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => $status]);
    }

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    $membership = $promotion->memberships()->where('user_id', $user->id)->first();

    expect($accepted)->toBeNull()
        ->and($membership?->status)->toBe($status);
})->with([
    'no membership' => [null],
    'suspended membership stays suspended' => [MembershipStatus::Suspended],
    'active membership stays as it is' => [MembershipStatus::Active],
]);

test('it never touches another users invitation', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $invitee = User::factory()->create(['status' => UserStatus::Active]);
    $stranger = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($invitee, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Invited]);

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $stranger);

    // Assert
    expect($accepted)->toBeNull()
        ->and($promotion->memberships()->where('user_id', $invitee->id)->firstOrFail()->status)->toBe(MembershipStatus::Invited)
        ->and($promotion->hasActiveMember($stranger))->toBeFalse();
});
