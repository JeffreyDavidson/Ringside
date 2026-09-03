<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Providers\AppServiceProvider;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

test('login screen can be rendered', function () {
    // Arrange
    $loginUrl = route('login');

    // Act
    $response = $this->get($loginUrl);

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
    $response = $this->post(route('login'), $credentials);

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
    $response = $this->from(route('login'))->post(route('login'), $credentials);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    assertGuest();
});
