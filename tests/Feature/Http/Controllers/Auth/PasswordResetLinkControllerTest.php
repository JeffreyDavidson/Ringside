<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\freezeTime;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

describe('create', function (): void {
    test('password reset link screen can be rendered', function () {
        // Arrange
        $passwordResetUrl = route('password.request');

        // Act
        $response = get($passwordResetUrl);

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('auth.forgot-password');
    });
});

describe('store', function (): void {
    test('password reset link requires a valid email address', function () {
        // Arrange
        $invalidData = ['email' => 'not-an-email'];

        // Act
        $response = from(route('password.request'))
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
        $response = post(route('password.email'), $requestData);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    })->with([
        'lowercase email' => 'promoter@example.com',
        'mixed-case email' => 'Promoter@Example.com',
    ]);

    test('registered and unregistered emails receive the same response', function (string $email): void {
        // Arrange
        Notification::fake();
        config(['auth.passwords.users.throttle' => 90]);
        freezeTime();
        User::factory()->create(['email' => 'promoter@example.com']);

        // Act
        $response = from(route('password.request'))
            ->post(route('password.email'), ['email' => $email]);

        // Assert
        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('_old_input')
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionHas('recovery_email', $email)
            ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);
    })->with([
        'registered' => 'promoter@example.com',
        'unregistered' => 'nobody@example.com',
    ]);

    test('only a registered email is sent a reset notification', function (): void {
        // Arrange
        Notification::fake();
        $user = User::factory()->create(['email' => 'promoter@example.com']);

        // Act
        $unknownResponse = post(route('password.email'), ['email' => 'nobody@example.com']);
        $response = post(route('password.email'), ['email' => $user->email]);

        // Assert
        $unknownResponse
            ->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'));
        $response
            ->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTimes(ResetPassword::class, 1);
        Notification::assertSentTo($user, ResetPassword::class);
    });

    test('a throttled request looks the same as a sent one and sends nothing', function (): void {
        // Arrange
        Notification::fake();
        config(['auth.passwords.users.throttle' => 90]);
        freezeTime();
        $user = User::factory()->create();
        $data = ['email' => $user->email];
        post(route('password.email'), $data);

        // Act
        $response = from(route('password.request'))
            ->post(route('password.email'), $data);

        // Assert
        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('_old_input')
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionHas('recovery_email', $user->email)
            ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    });

    test('a reset link can be resent after the broker cooldown', function (): void {
        // Arrange
        Notification::fake();
        config(['auth.passwords.users.throttle' => 90]);
        freezeTime();
        $user = User::factory()->create();
        $data = ['email' => $user->email];
        post(route('password.email'), $data);
        post(route('password.email'), $data);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        travel(91)->seconds();

        // Act
        $response = post(route('password.email'), $data);

        // Assert
        $response
            ->assertSessionHas('recovery_email', $user->email)
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentToTimes($user, ResetPassword::class, 2);
    });

    test('password reset works for a legacy user stored with a mixed-case email', function () {
        // Arrange
        Notification::fake();
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['email' => 'Legacy.Promoter@Example.com']);

        // Act
        $response = post(route('password.email'), ['email' => 'legacy.promoter@example.com']);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user->refresh(), ResetPassword::class);
    });
});
