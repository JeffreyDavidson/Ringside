<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

test('signed in users who are not active are logged out with a status message', function (UserStatus $status, string $messageKey): void {
    // Arrange
    $user = User::factory()
        ->create(['status' => UserStatus::Active]);

    actingAs($user);
    withSession(['private_session_marker' => 'account-only']);
    $user->update(['status' => $status]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __($messageKey))
        ->assertSessionMissing('private_session_marker');

    assertGuest();
})->with([
    'unverified' => [UserStatus::Unverified, 'auth-forms.account_pending'],
    'inactive' => [UserStatus::Inactive, 'auth-forms.account_inactive'],
]);

test('signed in users whose account no longer exists are logged out as inactive', function (): void {
    // Arrange
    $user = User::factory()
        ->create(['status' => UserStatus::Active]);

    actingAs($user);
    withSession(['private_session_marker' => 'account-only']);
    $user->forceDelete();

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_inactive'))
        ->assertSessionMissing('private_session_marker');

    assertGuest();
});

test('active users continue to the requested page', function (): void {
    // Arrange
    $user = administrator();
    actingAs($user);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk();

    assertAuthenticatedAs($user);
});

test('guests continue to public pages without being logged out', function (): void {
    // Arrange
    withSession(['public_session_marker' => 'guest-preference']);

    // Act
    $response = get(route('login'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('auth.login')
        ->assertSessionHas('public_session_marker', 'guest-preference');

    assertGuest();
});
