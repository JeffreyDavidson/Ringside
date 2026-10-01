<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

test('signed in users who are not active are logged out with a status message', function (UserStatus $status, string $messageKey) {
    // Arrange
    $user = User::factory()->create(['status' => UserStatus::Active]);
    actingAs($user);
    $user->update(['status' => $status]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __($messageKey));
    assertGuest();
})->with([
    'unverified' => [UserStatus::Unverified, 'auth-forms.account_pending'],
    'inactive' => [UserStatus::Inactive, 'auth-forms.account_inactive'],
]);

test('signed in users whose account no longer exists are logged out as inactive', function () {
    // Arrange
    $user = User::factory()->create(['status' => UserStatus::Active]);
    actingAs($user);
    $user->forceDelete();

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_inactive'));
    assertGuest();
});

test('active users continue to the requested page', function () {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response->assertSuccessful();
});
