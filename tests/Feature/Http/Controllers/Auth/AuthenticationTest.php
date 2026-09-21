<?php

declare(strict_types=1);

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

test('users can authenticate using the login screen', function () {
    // Arrange
    $user = User::factory()->create();
    $credentials = [
        'email' => $user->email,
        'password' => 'secret',
    ];

    // Act
    $response = post(route('login'), $credentials);

    // Assert
    $response->assertRedirect(AppServiceProvider::HOME);
    assertAuthenticated();
});

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
