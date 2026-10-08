<?php

declare(strict_types=1);

use App\Builders\Users\UserBuilder;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('user queries use the typed builder', function () {
    expect(User::query())->toBeInstanceOf(UserBuilder::class)
        ->and(User::query()->whereNameMatches('Jeffrey'))->toBeInstanceOf(UserBuilder::class);
});

test('user status filtering composes with name search and excludes deleted users', function (UserStatus $status) {
    // Arrange
    $user = User::factory()->create([
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'status' => $status,
    ]);
    User::factory()->trashed()->create([
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'status' => $status,
    ]);
    User::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'status' => $status,
    ]);

    foreach (UserStatus::cases() as $otherStatus) {
        if ($otherStatus === $status) {
            continue;
        }

        User::factory()->create([
            'first_name' => 'Jeffrey',
            'last_name' => 'Davidson',
            'status' => $otherStatus,
        ]);
    }

    // Act
    $query = User::query();
    $query->whereStatus($status);
    $query->whereNameMatches('Davidson');
    $users = $query->get();

    // Assert
    expect($users->modelKeys())->toBe([$user->id]);
})->with(UserStatus::cases());

test('it keeps only users who belong to the promotion', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    User::factory()->create();
    $promotion->users()->attach($member, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    Promotion::factory()->create()->users()->attach($otherMember, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);

    // Act
    $users = User::query()
        ->memberOfPromotion($promotion)
        ->get();

    // Assert
    expect($users->modelKeys())->toBe([$member->id]);
});
