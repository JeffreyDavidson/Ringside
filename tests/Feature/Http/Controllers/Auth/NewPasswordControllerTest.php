<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('a failed reset gives the same error for a registered and an unknown email', function (string $email): void {
    // Arrange
    $user = User::factory()->create(['email' => 'registered@example.com']);
    $password = $user->password;
    $rememberToken = $user->remember_token;
    Event::fake([PasswordReset::class]);

    // Act
    $response = from(route('password.reset', 'bogus-token'))
        ->post(route('password.update'), [
            'token' => 'bogus-token',
            'email' => $email,
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ]);

    // Assert
    $response
        ->assertRedirect(route('password.reset', 'bogus-token'))
        ->assertSessionHasErrors(['email' => __('passwords.token')])
        ->assertSessionHasInput('email', $email)
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    expect($user->refresh()->password)->toBe($password)
        ->and($user->remember_token)->toBe($rememberToken);
    Event::assertNotDispatched(PasswordReset::class);
})->with([
    'a registered email' => 'registered@example.com',
    'an unknown email' => 'unknown@example.com',
]);

test('a valid token resets the password', function (string $email): void {
    // Arrange
    $user = User::factory()->create(['email' => 'promoter@example.com']);
    $rememberToken = $user->remember_token;
    $token = Password::createToken($user);
    Event::fake([PasswordReset::class]);

    // Act
    $response = post(route('password.update'), [
        'token' => $token,
        'email' => $email,
        'password' => 'new-password-12345',
        'password_confirmation' => 'new-password-12345',
    ]);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    expect(Hash::check('new-password-12345', $user->refresh()->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($rememberToken)
        ->and(Password::tokenExists($user, $token))->toBeFalse();
    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user instanceof User && $event->user->is($user));
    Event::assertDispatchedOnce(PasswordReset::class);
})->with(['promoter@example.com', 'Promoter@Example.com']);

test('the reset form receives its token and email', function (): void {
    // Arrange
    $token = 'reset-token-from-link';
    $email = 'promoter@example.com';

    // Act
    $response = get(route('password.reset', ['token' => $token, 'email' => $email]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('auth.passwords.reset')
        ->assertViewHas('token', $token)
        ->assertViewHas('email', $email);
});
