<?php

declare(strict_types=1);

use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Exceptions\Promotions\CannotRemoveLastOwnerException;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

function attachMember(
    Promotion $promotion,
    MembershipRole $role,
    MembershipStatus $status = MembershipStatus::Active,
    UserStatus $userStatus = UserStatus::Active,
): User {
    $user = User::factory()->create(['status' => $userStatus]);
    $promotion->users()->attach($user, ['role' => $role, 'status' => $status]);

    return $user;
}

dataset('owner-removing changes', [
    'demote to manager' => [fn (Promotion $p, User $u) => app(UpdatePromotionMemberRoleAction::class)->handle($p, $u, MembershipRole::Manager)],
    'demote to member' => [fn (Promotion $p, User $u) => app(UpdatePromotionMemberRoleAction::class)->handle($p, $u, MembershipRole::Member)],
    'suspend' => [fn (Promotion $p, User $u) => app(UpdatePromotionMemberStatusAction::class)->handle($p, $u, MembershipStatus::Suspended)],
]);

test('the sole active owner cannot be demoted or suspended', function (Closure $change): void {
    $promotion = Promotion::factory()->create();
    $owner = attachMember($promotion, MembershipRole::Owner);

    expect(fn () => $change($promotion, $owner))->toThrow(CannotRemoveLastOwnerException::class);

    $membership = $promotion->memberships()->where('user_id', $owner->id)->firstOrFail();
    expect($membership->role)->toBe(MembershipRole::Owner)
        ->and($membership->status)->toBe(MembershipStatus::Active);
})->with('owner-removing changes');

test('a suspended second owner does not count as another active owner', function (Closure $change): void {
    $promotion = Promotion::factory()->create();
    $owner = attachMember($promotion, MembershipRole::Owner);
    attachMember($promotion, MembershipRole::Owner, MembershipStatus::Suspended);

    expect(fn () => $change($promotion, $owner))->toThrow(CannotRemoveLastOwnerException::class);
})->with('owner-removing changes');

test('an owner whose user account is not active does not count as another active owner', function (UserStatus $userStatus, Closure $change): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $owner = attachMember($promotion, MembershipRole::Owner);
    attachMember($promotion, MembershipRole::Owner, userStatus: $userStatus);

    // Act
    $attempt = fn () => $change($promotion, $owner);

    // Assert
    expect($attempt)->toThrow(CannotRemoveLastOwnerException::class, "{$promotion->name} must keep at least one active owner.");
})->with([
    'inactive account' => UserStatus::Inactive,
    'unverified account' => UserStatus::Unverified,
])->with('owner-removing changes');

test('an owner can be demoted or suspended while another active owner remains', function (Closure $change): void {
    $promotion = Promotion::factory()->create();
    $owner = attachMember($promotion, MembershipRole::Owner);
    attachMember($promotion, MembershipRole::Owner);

    $change($promotion, $owner);

    $membership = $promotion->memberships()->where('user_id', $owner->id)->firstOrFail();
    expect($membership->role === MembershipRole::Owner && $membership->status === MembershipStatus::Active)->toBeFalse();
})->with('owner-removing changes');

test('non-owners and non-active owners are never blocked', function (): void {
    $promotion = Promotion::factory()->create();
    $member = attachMember($promotion, MembershipRole::Member);
    $suspendedOwner = attachMember($promotion, MembershipRole::Owner, MembershipStatus::Suspended);

    app(UpdatePromotionMemberStatusAction::class)->handle($promotion, $member, MembershipStatus::Suspended);
    app(UpdatePromotionMemberRoleAction::class)->handle($promotion, $suspendedOwner, MembershipRole::Member);

    expect($promotion->memberships()->where('user_id', $member->id)->firstOrFail()->status)->toBe(MembershipStatus::Suspended)
        ->and($promotion->memberships()->where('user_id', $suspendedOwner->id)->firstOrFail()->role)->toBe(MembershipRole::Member);
});

test('keeping the owner role or staying active never trips the guard', function (): void {
    $promotion = Promotion::factory()->create();
    $owner = attachMember($promotion, MembershipRole::Owner);

    app(UpdatePromotionMemberRoleAction::class)->handle($promotion, $owner, MembershipRole::Owner);
    app(UpdatePromotionMemberStatusAction::class)->handle($promotion, $owner, MembershipStatus::Active);

    expect($promotion->memberships()->where('user_id', $owner->id)->firstOrFail()->role)->toBe(MembershipRole::Owner);
});
