<?php

declare(strict_types=1);

use function Pest\Laravel\post;

test('guest account endpoints stop responding after six requests a minute', function (string $routeName): void {
    // Arrange
    $url = route($routeName);
    foreach (range(1, 6) as $attempt) {
        post($url, []);
    }

    // Act
    $response = post($url, []);

    // Assert
    $response->assertTooManyRequests();
})->with(['register', 'password.email', 'password.update']);

test('the guest account endpoints keep separate rate limits', function (): void {
    // Arrange
    foreach (range(1, 6) as $attempt) {
        post(route('register'), []);
    }

    // Act
    $response = post(route('password.email'), []);

    // Assert
    $response->assertSessionHasErrors('email');
});

test('the guest account endpoints accept requests again after the window passes', function (): void {
    // Arrange
    foreach (range(1, 7) as $attempt) {
        post(route('register'), []);
    }
    $this->travel(61)->seconds();

    // Act
    $response = post(route('register'), []);

    // Assert
    $response->assertSessionHasErrors('email');
});

test('login stops responding after twenty failed attempts from one address across different emails', function (): void {
    // Arrange
    foreach (range(1, 20) as $attempt) {
        post(route('login'), ['email' => "visitor{$attempt}@example.com", 'password' => 'wrong-password']);
    }

    // Act
    $response = post(route('login'), ['email' => 'visitor21@example.com', 'password' => 'wrong-password']);

    // Assert
    $response->assertTooManyRequests();
});

test('login still answers a failed attempt below the per address limit', function (): void {
    // Arrange
    foreach (range(1, 19) as $attempt) {
        post(route('login'), ['email' => "visitor{$attempt}@example.com", 'password' => 'wrong-password']);
    }

    // Act
    $response = post(route('login'), ['email' => 'visitor20@example.com', 'password' => 'wrong-password']);

    // Assert
    $response->assertSessionHasErrors('email');
});
