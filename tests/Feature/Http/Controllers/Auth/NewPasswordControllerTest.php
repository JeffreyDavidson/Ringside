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

describe('create', function (): void {
    test('the reset form receives its token and email', function (): void {
        // Arrange
        $token = 'reset-token-from-link';
        $email = 'promoter@example.com';

        // Act
        $response = get(route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('auth.passwords.reset')
            ->assertViewHas('token', $token)
            ->assertViewHas('email', $email);
    });
});

describe('store', function (): void {
    test('a failed reset gives the same error for a registered and an unknown email', function (string $email): void {
        // Arrange
        $user = User::factory()
            ->create(['email' => 'registered@example.com']);
        $password = $user->password;
        $rememberToken = $user->remember_token;

        Event::fake([PasswordReset::class]);

        $requestData = [
            'token' => 'bogus-token',
            'email' => $email,
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ];

        // Act
        $response = from(route('password.reset', 'bogus-token'))
            ->post(route('password.update'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('password.reset', 'bogus-token'))
            ->assertSessionHasErrors(['email' => __('passwords.token')])
            ->assertSessionHasInput('email', $email)
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        expect($user->refresh()->password)->toBe($password)
            ->and($user->remember_token)
            ->toBe($rememberToken);
        Event::assertNotDispatched(PasswordReset::class);
    })->with([
        'a registered email' => 'registered@example.com',
        'an unknown email' => 'unknown@example.com',
    ]);

    test('a valid token resets the password', function (string $email): void {
        // Arrange
        $user = User::factory()
            ->create(['email' => 'promoter@example.com']);
        $rememberToken = $user->remember_token;
        $token = Password::createToken($user);

        Event::fake([PasswordReset::class]);

        $requestData = [
            'token' => $token,
            'email' => $email,
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ];

        // Act
        $response = post(route('password.update'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        expect(Hash::check('new-password-12345', $user->refresh()->password))->toBeTrue()
            ->and($user->remember_token)->not->toBe($rememberToken)
            ->and(Password::tokenExists($user, $token))
            ->toBeFalse();
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user instanceof User && $event->user->is($user));
        Event::assertDispatchedOnce(PasswordReset::class);
    })->with([
        'lowercase email' => 'promoter@example.com',
        'mixed-case email' => 'Promoter@Example.com',
        'surrounding whitespace' => '  Promoter@Example.com  ',
    ]);
    test('missing reset fields cannot change the password or consume the token', function (string $field): void {
        // Arrange
        $user = User::factory()
            ->create(['email' => 'promoter@example.com']);
        $original = $user->refresh()
            ->getAttributes();
        $token = Password::createToken($user);
        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ];
        unset($payload[$field]);

        Event::fake([PasswordReset::class]);

        // Act
        $response = from(route('password.reset', $token))->post(route('password.update'), $payload);

        // Assert
        $response
            ->assertRedirect(route('password.reset', $token))
            ->assertSessionHasErrors($field)
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        expect($user->refresh()
            ->getAttributes())->toBe($original)
            ->and(Password::tokenExists($user, $token))
            ->toBeTrue();
        Event::assertNotDispatched(PasswordReset::class);
    })->with([
        'token' => 'token',
        'email' => 'email',
        'password' => 'password',
    ]);

    test('mismatched password confirmation cannot change the password or consume the token', function (): void {
        // Arrange
        $user = User::factory()
            ->create(['email' => 'promoter@example.com']);
        $original = $user->refresh()
            ->getAttributes();
        $token = Password::createToken($user);
        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-12345',
            'password_confirmation' => 'different-password-12345',
        ];

        Event::fake([PasswordReset::class]);

        // Act
        $response = from(route('password.reset', $token))->post(route('password.update'), $payload);

        // Assert
        $response
            ->assertRedirect(route('password.reset', $token))
            ->assertSessionHasErrors('password')
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        expect($user->refresh()
            ->getAttributes())->toBe($original)
            ->and(Password::tokenExists($user, $token))
            ->toBeTrue();
        Event::assertNotDispatched(PasswordReset::class);
    });
});

describe('email preparation', function (): void {
    test('null and non-string email values remain validation failures', function (mixed $email): void {
        // Arrange
        $requestData = [
            'token' => 'invalid-token',
            'password' => 'password-12345',
            'password_confirmation' => 'password-12345',
            'email' => $email,
        ];

        // Act
        $response = post(route('password.update'), $requestData);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        expect(User::query()
            ->count())->toBe(0);
    })->with([
        'null email' => null,
        'array email' => [['unexpected']],
        'numeric email' => 123,
    ]);
});

test('a refused reset retains normalized email without changing the password or token', function (): void {
    // Arrange
    $user = User::factory()
        ->create(['email' => 'promoter@example.com']);
    $originalPassword = $user->password;
    $validToken = Password::createToken($user);

    Event::fake([PasswordReset::class]);

    $requestData = [
        'email' => '  Promoter@Example.COM  ',
        'token' => 'invalid-token',
        'password' => 'new-password-12345',
        'password_confirmation' => 'new-password-12345',
    ];

    // Act
    $response = post(route('password.update'), $requestData);

    // Assert
    $response
        ->assertRedirect()
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email', 'promoter@example.com')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    expect($user->refresh()->password)->toBe($originalPassword)
        ->and(Password::tokenExists($user, $validToken))
        ->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
});
