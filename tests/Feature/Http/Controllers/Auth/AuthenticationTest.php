<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use App\Providers\AppServiceProvider;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('login screen can be rendered', function () {
    // Arrange
    $loginUrl = route('login');

    // Act
    $response = get($loginUrl);

    // Assert
    $response->assertSuccessful();
});

test('users can authenticate using the login screen', function (string $email) {
    // Arrange
    User::factory()->create([
        'email' => 'promoter@example.com',
        'email_verified_at' => null,
        'status' => UserStatus::Active,
    ]);
    $credentials = [
        'email' => $email,
        'password' => 'secret',
    ];

    // Act
    $response = post(route('login'), $credentials);

    // Assert
    $response->assertRedirect(AppServiceProvider::HOME);
    assertAuthenticated();
})->with(['promoter@example.com', 'Promoter@Example.com']);

test('users can not authenticate with invalid password', function () {
    // Arrange
    $user = User::factory()->create();
    $credentials = [
        'email' => $user->email,
        'password' => 'wrong-password',
    ];

    // Act
    $response = from(route('login'))->post(route('login'), $credentials);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    assertGuest();
});

test('only active users can authenticate', function (UserStatus $status) {
    // Arrange
    $user = User::factory()->create(['status' => $status]);

    // Act
    $response = from(route('login'))->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret',
    ]);

    // Assert
    $response->assertRedirect(route('login'))->assertSessionHasErrors('email');
    assertGuest();
})->with([
    'unverified' => UserStatus::Unverified,
    'inactive' => UserStatus::Inactive,
]);

test('deactivated users are logged out on their next request', function () {
    // Arrange
    $user = User::factory()->create(['status' => UserStatus::Active]);
    actingAs($user);
    $user->update(['status' => UserStatus::Inactive]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_inactive'));
    assertGuest();
});

test('login requires credentials', function () {
    // Arrange
    $credentials = [];

    // Act
    $response = post(route('login'), $credentials);

    // Assert
    $response->assertSessionHasErrors(['email', 'password']);
    assertGuest();
});

test('authenticated users can log out', function () {
    // Arrange
    actingAs(administrator());

    // Act
    $response = post(route('logout'));

    // Assert
    $response->assertRedirect(route('login'));
    assertGuest();
});
