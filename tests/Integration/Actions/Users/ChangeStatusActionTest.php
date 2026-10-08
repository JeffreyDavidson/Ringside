<?php

declare(strict_types=1);

use App\Actions\Users\ChangeStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Exceptions\Promotions\CannotRemoveLastOwnerException;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

use function Pest\Laravel\assertDatabaseHas;

test('it changes a user status without changing email verification', function (UserStatus $status): void {
    $user = User::factory()->unverified()->create([
        'email_verified_at' => null,
    ]);

    $updatedUser = resolve(ChangeStatusAction::class)->handle($user, $status);

    expect($updatedUser->status)->toBe($status)
        ->and($updatedUser->email_verified_at)->toBeNull();

    assertDatabaseHas('users', [
        'id' => $user->getKey(),
        'status' => $status->value,
        'email_verified_at' => null,
    ]);
})->with([
    'active' => UserStatus::Active,
    'inactive' => UserStatus::Inactive,
    'unverified' => UserStatus::Unverified,
]);

test('it uses the current persisted user state when changing status', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $staleUser = $user->replicate(['id']);
    $staleUser->id = $user->id;
    $staleUser->exists = true;
    $user->update(['status' => UserStatus::Inactive]);

    $updatedUser = resolve(ChangeStatusAction::class)->handle($staleUser, UserStatus::Active);

    $persistedUser = User::query()
        ->whereKey($user->getKey())
        ->firstOrFail();

    expect($updatedUser->status)->toBe(UserStatus::Active)
        ->and($persistedUser->status)->toBe(UserStatus::Active);
});

describe('promotion owners', function (): void {
    it('rejects deactivating the only active owner of a promotion', function (UserStatus $status): void {
        // Arrange
        $owner = User::factory()->create(['status' => UserStatus::Active]);
        $sharedPromotion = Promotion::factory()->create(['name' => 'Shared Wrestling']);
        $soleOwnedPromotion = Promotion::factory()->create(['name' => 'Solo Wrestling']);
        $sharedPromotion->users()->attach($owner, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        $sharedPromotion->users()->attach(
            User::factory()->create(['status' => UserStatus::Active]),
            ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active],
        );
        $soleOwnedPromotion->users()->attach($owner, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);

        // Act
        $attempt = fn () => resolve(ChangeStatusAction::class)->handle($owner, $status);

        // Assert
        expect($attempt)->toThrow(CannotRemoveLastOwnerException::class, 'Solo Wrestling must keep at least one active owner.')
            ->and($owner->refresh()->status)->toBe(UserStatus::Active);
    })->with([
        'inactive' => UserStatus::Inactive,
        'unverified' => UserStatus::Unverified,
    ]);

    it('deactivates an owner while another owner with an active account remains', function (): void {
        // Arrange
        $owner = User::factory()->create(['status' => UserStatus::Active]);
        $promotion = Promotion::factory()->create();
        $promotion->users()->attach($owner, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        $promotion->users()->attach(
            User::factory()->create(['status' => UserStatus::Active]),
            ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active],
        );

        // Act
        $updatedUser = resolve(ChangeStatusAction::class)->handle($owner, UserStatus::Inactive);

        // Assert
        expect($updatedUser->status)->toBe(UserStatus::Inactive);
    });

    it('changes the status of an unverified sole owner, who already does not count as an active owner', function (): void {
        // Arrange
        $user = User::factory()->create(['status' => UserStatus::Unverified]);
        Promotion::factory()->create()->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);

        // Act
        $updatedUser = resolve(ChangeStatusAction::class)->handle($user, UserStatus::Inactive);

        // Assert
        expect($updatedUser->status)->toBe(UserStatus::Inactive);
    });

    it('deactivates a user whose only owner membership is suspended', function (): void {
        // Arrange
        $user = User::factory()->create(['status' => UserStatus::Active]);
        Promotion::factory()->create()->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Suspended]);

        // Act
        $updatedUser = resolve(ChangeStatusAction::class)->handle($user, UserStatus::Inactive);

        // Assert
        expect($updatedUser->status)->toBe(UserStatus::Inactive);
    });
});
