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

    test('registered emails receive the common recovery response and one reset notification', function (string $email): void {
        // Arrange
        $user = User::factory()
            ->create(['email' => 'promoter@example.com']);
        config(['auth.passwords.users.throttle' => 90]);
        $requestData = ['email' => $email];

        Notification::fake();

        // Act
        $response = from(route('password.request'))->post(route('password.email'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('_old_input')
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionHas('recovery_email', 'promoter@example.com')
            ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertSentTimes(ResetPassword::class, 1);
    })->with([
        'lowercase email' => 'promoter@example.com',
        'mixed-case email' => 'Promoter@Example.com',
        'surrounding whitespace' => '  Promoter@Example.com  ',
    ]);

    test('an unknown email receives the common recovery response without a notification', function (): void {
        // Arrange
        User::factory()
            ->create(['email' => 'promoter@example.com']);
        config(['auth.passwords.users.throttle' => 90]);
        $requestData = ['email' => 'nobody@example.com'];

        Notification::fake();

        // Act
        $response = from(route('password.request'))->post(route('password.email'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('_old_input')
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionHas('recovery_email', 'nobody@example.com')
            ->assertSessionHas('recovery_resend_at', now()->addSeconds(90)->timestamp);

        Notification::assertNothingSent();
    });

    test('a throttled request looks the same as a sent one and sends nothing', function (): void {
        // Arrange
        Notification::fake();
        config(['auth.passwords.users.throttle' => 90]);
        freezeTime();
        $user = User::factory()
            ->create();
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
        $user = User::factory()
            ->create();
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
        $user = User::factory()
            ->create();
        DB::table('users')
            ->where('id', $user->id)
            ->update(['email' => 'Legacy.Promoter@Example.com']);

        $requestData = ['email' => 'legacy.promoter@example.com'];

        // Act
        $response = post(route('password.email'), $requestData);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user->refresh(), ResetPassword::class);
    });
});

describe('email preparation', function (): void {
    test('null and non-string email values remain validation failures', function (mixed $email): void {
        // Arrange
        $requestData = [
            'email' => $email,
        ];

        // Act
        $response = post(route('password.email'), $requestData);

        // Assert
        $response
            ->assertRedirect()
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', fn (mixed $input): bool => $input === $email)
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

test('invalid recovery input retains the entered email case without a notification', function (): void {
    // Arrange
    Notification::fake();

    $requestData = ['email' => '  Invalid@@EMAIL  '];

    // Act
    $response = from(route('password.request'))
        ->post(route('password.email'), $requestData);

    // Assert
    $response
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email', 'Invalid@@EMAIL');

    Notification::assertNothingSent();
});
