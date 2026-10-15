<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

test('registration rejects an eleven character password', function (): void {
    // Arrange
    $registrationData = [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'email' => 'jeffrey@example.com',
        'password' => 'elevenchars',
        'password_confirmation' => 'elevenchars',
    ];

    Event::fake([Registered::class]);

    // Act
    $response = post(route('register'), $registrationData);

    // Assert
    $response
        ->assertSessionHasErrors('password')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    expect(User::query()
        ->count())->toBe(0);
    Event::assertNotDispatched(Registered::class);
    assertGuest();
});

test('registration accepts a twelve character password', function (): void {
    // Arrange
    $registrationData = [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'email' => 'jeffrey@example.com',
        'password' => 'twelve-chars',
        'password_confirmation' => 'twelve-chars',
    ];

    Event::fake([Registered::class]);

    // Act
    $response = post(route('register'), $registrationData);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('auth-forms.account_pending'));

    $user = User::query()
        ->sole();
    expect(Hash::check('twelve-chars', $user->password))->toBeTrue();
    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user instanceof User && $event->user->is($user));
    Event::assertDispatchedOnce(Registered::class);
    assertGuest();
});

test('password reset rejects passwords shorter than twelve characters', function (): void {
    // Arrange
    $user = User::factory()
        ->create();
    $originalPassword = $user->password;
    $token = Password::createToken($user);

    Event::fake([PasswordReset::class]);

    $requestData = [
        'token' => $token,
        'email' => $user->email,
        'password' => 'elevenchars',
        'password_confirmation' => 'elevenchars',
    ];

    // Act
    $response = post(route('password.update'), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('password')
        ->assertSessionMissing('_old_input.password');

    expect($user->refresh()->password)->toBe($originalPassword)
        ->and(Password::tokenExists($user, $token))
        ->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
});
