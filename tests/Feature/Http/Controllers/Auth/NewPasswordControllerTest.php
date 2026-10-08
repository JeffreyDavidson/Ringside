<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\from;
use function Pest\Laravel\post;

test('a failed reset gives the same error for a registered and an unknown email', function (string $email): void {
    // Arrange
    User::factory()->create(['email' => 'registered@example.com']);

    // Act
    $response = from(route('password.reset', 'bogus-token'))
        ->post(route('password.update'), [
            'token' => 'bogus-token',
            'email' => $email,
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ]);

    // Assert
    $response->assertRedirect(route('password.reset', 'bogus-token'))
        ->assertSessionHasErrors(['email' => __('passwords.token')])
        ->assertSessionHasInput('email', $email)
        ->assertSessionMissing('_old_input.password');
})->with([
    'a registered email' => 'registered@example.com',
    'an unknown email' => 'unknown@example.com',
]);

test('a valid token resets the password', function (): void {
    // Arrange
    $user = User::factory()->create();
    $token = Password::createToken($user);

    // Act
    $response = post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-12345',
        'password_confirmation' => 'new-password-12345',
    ]);

    // Assert
    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));
    expect(Hash::check('new-password-12345', $user->refresh()->password))->toBeTrue();
});
