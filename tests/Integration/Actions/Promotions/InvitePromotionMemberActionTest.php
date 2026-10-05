<?php

declare(strict_types=1);

use App\Actions\Promotions\InvitePromotionMemberAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('it invites an active user to the selected promotion with the given role and grants no access yet', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    // Act
    $invited = app(InvitePromotionMemberAction::class)->handle($promotion, $user, MembershipRole::Manager);

    // Assert
    $membership = $promotion->memberships()->where('user_id', $user->id)->firstOrFail();

    expect($invited)->toBeTrue()
        ->and($membership->role)->toBe(MembershipRole::Manager)
        ->and($membership->status)->toBe(MembershipStatus::Invited)
        ->and($promotion->hasActiveMember($user))->toBeFalse();
});

test('it leaves an existing membership unchanged when the user is invited again', function (MembershipStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => $status,
    ]);

    // Act
    $invited = app(InvitePromotionMemberAction::class)->handle($promotion, $user, MembershipRole::Owner);

    // Assert
    $memberships = $promotion->memberships()->where('user_id', $user->id)->get();

    expect($invited)->toBeFalse()
        ->and($memberships)->toHaveCount(1)
        ->and($memberships->firstOrFail()->role)->toBe(MembershipRole::Member)
        ->and($memberships->firstOrFail()->status)->toBe($status);
})->with([
    'active membership' => MembershipStatus::Active,
    'suspended membership' => MembershipStatus::Suspended,
    'invited membership' => MembershipStatus::Invited,
]);

test('it never invites an account that is not active', function (UserStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => $status]);

    // Act
    $attempt = fn () => app(InvitePromotionMemberAction::class)->handle($promotion, $user, MembershipRole::Member);

    // Assert
    expect($attempt)->toThrow(ModelNotFoundException::class)
        ->and($promotion->memberships()->count())->toBe(0);
})->with([
    'inactive account' => UserStatus::Inactive,
    'unverified account' => UserStatus::Unverified,
]);
