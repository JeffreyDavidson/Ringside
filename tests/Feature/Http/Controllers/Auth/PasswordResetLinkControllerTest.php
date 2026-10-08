<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\freezeTime;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

test('password reset link screen can be rendered', function () {
    // Arrange
    $passwordResetUrl = route('password.request');

    // Act
    $response = get($passwordResetUrl);

    // Assert
    $response->assertSuccessful();
});

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
    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
})->with(['promoter@example.com', 'Promoter@Example.com']);

test('password reset form can be rendered', function () {
    // Arrange
    Notification::fake();
    $user = User::factory()->create();

    // Act
    post(route('password.email'), ['email' => $user->email]);

    // Assert
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $response = get(route('password.reset', [
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
    post(route('password.email'), ['email' => $user->email]);

    // Assert
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($email): bool {
        $response = post(route('password.update'), [
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
    $response->assertRedirect(route('password.request'))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('_old_input')
        ->assertSessionHas('status', __('passwords.sent'))
        ->assertSessionHas('recovery_email', $email)
        ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);
})->with(['registered' => 'promoter@example.com', 'unregistered' => 'nobody@example.com']);

test('only a registered email is sent a reset notification', function (): void {
    // Arrange
    Notification::fake();
    $user = User::factory()->create(['email' => 'promoter@example.com']);

    // Act
    post(route('password.email'), ['email' => 'nobody@example.com']);
    post(route('password.email'), ['email' => $user->email]);

    // Assert
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
    $throttled = from(route('password.request'))
        ->post(route('password.email'), $data);

    // Assert
    $throttled->assertRedirect(route('password.request'))
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
    $resent = post(route('password.email'), $data);

    // Assert
    $resent->assertSessionHas('recovery_email', $user->email)
        ->assertSessionHas('status', __('passwords.sent'));
    Notification::assertSentToTimes($user, ResetPassword::class, 2);
});

test('password reset works for a legacy user stored with a mixed-case email', function () {
    // Arrange
    Notification::fake();
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['email' => 'Legacy.Promoter@Example.com']);

    // Act
    post(route('password.email'), ['email' => 'legacy.promoter@example.com']);

    // Assert
    Notification::assertSentTo($user->refresh(), ResetPassword::class);
});
