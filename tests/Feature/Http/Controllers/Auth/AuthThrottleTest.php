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
