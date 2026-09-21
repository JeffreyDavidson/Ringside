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

test('password reset link can be requested', function (string $email) {
    // Arrange
    Notification::fake();
    $user = User::factory()->create(['email' => 'promoter@example.com']);
    $requestData = ['email' => $email];

    // Act
    $response = $this->post(route('password.email'), $requestData);

    // Assert
    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
})->with(['promoter@example.com', 'Promoter@Example.com']);

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

test('password can be reset with a valid token', function (string $email) {
    // Arrange
    Notification::fake();
    $user = User::factory()->create(['email' => 'promoter@example.com']);

    // Act
    $this->post(route('password.email'), ['email' => $user->email]);

    // Assert
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($email): bool {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();

        return true;
    });

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
})->with(['promoter@example.com', 'Promoter@Example.com']);

test('a reset link can be resent after the broker cooldown', function (): void {
    // Arrange
    Notification::fake();
    config(['auth.passwords.users.throttle' => 90]);
    $this->freezeTime();
    $user = User::factory()->create();
    $data = ['email' => $user->email];

    // Act
    $first = $this->from(route('password.request'))
        ->post(route('password.email'), $data);

    // Assert
    $first->assertSessionHas('recovery_email', $user->email)
        ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);

    // Act
    $throttled = $this->post(route('password.email'), $data);

    // Assert
    $throttled->assertSessionHasErrors('email')
        ->assertSessionHas('recovery_email', $user->email)
        ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);
    Notification::assertSentToTimes($user, ResetPassword::class, 1);

    // Arrange
    $this->travel(91)->seconds();

    // Act
    $resent = $this->post(route('password.email'), $data);

    // Assert
    $resent->assertSessionHas('recovery_email', $user->email)
        ->assertSessionHas('status', __('passwords.sent'));
    Notification::assertSentToTimes($user, ResetPassword::class, 2);
});
