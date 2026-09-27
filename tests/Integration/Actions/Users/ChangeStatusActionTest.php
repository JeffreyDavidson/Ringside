<?php

declare(strict_types=1);

use App\Actions\Users\ChangeStatusAction;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;

test('it changes a user status without changing email verification', function (UserStatus $status): void {
    $user = User::factory()->unverified()->create([
        'email_verified_at' => null,
    ]);

    $updatedUser = resolve(ChangeStatusAction::class)->handle($user, $status);

    expect($updatedUser->status)->toBe($status)
        ->and($updatedUser->email_verified_at)->toBeNull();

    $this->assertDatabaseHas('users', [
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
