<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('password reset link screen can be rendered', function () {
    // Arrange
    $passwordResetUrl = route('password.request');

    // Act
    $response = $this->get($passwordResetUrl);

    // Assert
    $response->assertSuccessful();
});

test('password reset link requires a valid email address', function () {
    // Arrange
    $invalidData = ['email' => 'not-an-email'];

    // Act
    $response = $this->from(route('password.request'))
        ->post(route('password.email'), $invalidData);

    // Assert
    $response
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email');
});

test('password reset link can be requested', function () {
    // Arrange
    Notification::fake();
    $user = User::factory()->create();
    $requestData = ['email' => $user->email];

    // Act
    $response = $this->post(route('password.email'), $requestData);

    // Assert
    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset form can be rendered', function () {
    // Arrange
    Notification::fake();
    $user = User::factory()->create();

    // Act
    $this->post(route('password.email'), ['email' => $user->email]);

    // Assert
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $response = $this->get(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]));

        $response->assertSuccessful();
        $response->assertViewIs('auth.passwords.reset');

        return true;
    });
});

test('password can be reset with a valid token', function () {
    // Arrange
    Notification::fake();
    $user = User::factory()->create();

    // Act
    $this->post(route('password.email'), ['email' => $user->email]);

    // Assert
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();

        return true;
    });

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});
