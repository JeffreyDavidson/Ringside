<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;

test('it filters memberships by user and active status', function (): void {
    // Arrange
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $activePromotion = Promotion::factory()->create();
    $suspendedPromotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();

    $activePromotion->users()->attach($user, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Active,
    ]);
    $suspendedPromotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Suspended,
    ]);
    $otherPromotion->users()->attach($otherUser, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    // Act
    $memberships = PromotionMembership::query()
        ->forUser($user)
        ->active()
        ->get();

    // Assert
    expect($memberships)->toHaveCount(1)
        ->and($memberships->sole()->promotion_id)->toBe($activePromotion->id);
});

test('it filters memberships to users whose account is active', function (UserStatus $status, bool $included): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => $status]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    // Act
    $exists = PromotionMembership::query()
        ->forUser($user)
        ->withActiveUser()
        ->exists();

    // Assert
    expect($exists)->toBe($included);
})->with([
    'active account' => [UserStatus::Active, true],
    'inactive account' => [UserStatus::Inactive, false],
    'unverified account' => [UserStatus::Unverified, false],
]);

test('it filters memberships by role', function (): void {
    // Arrange
    $user = User::factory()->create();
    $managerPromotion = Promotion::factory()->create();
    $ownerPromotion = Promotion::factory()->create();

    $managerPromotion->users()->attach($user, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Active,
    ]);
    $ownerPromotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    // Act
    $memberships = PromotionMembership::query()
        ->forUser($user)
        ->active()
        ->withRole(MembershipRole::Owner)
        ->get();

    // Assert
    expect($memberships)->toHaveCount(1)
        ->and($memberships->sole()->promotion_id)->toBe($ownerPromotion->id);
});
