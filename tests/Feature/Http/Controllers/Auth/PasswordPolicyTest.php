<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\post;

test('registration enforces a twelve character password minimum', function (string $password, bool $isAccepted): void {
    // Arrange
    $registrationData = [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'email' => 'jeffrey@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ];

    // Act
    $response = post(route('register'), $registrationData);

    // Assert
    if ($isAccepted) {
        $response->assertSessionHasNoErrors();

        return;
    }

    $response->assertSessionHasErrors('password');
})->with([
    'eleven characters' => ['elevenchars', false],
    'twelve characters' => ['twelve-chars', true],
]);

test('password reset rejects passwords shorter than twelve characters', function (): void {
    // Arrange
    $user = User::factory()->create();
    $originalPassword = $user->password;
    $token = Password::createToken($user);

    Event::fake([PasswordReset::class]);

    // Act
    $response = post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'elevenchars',
        'password_confirmation' => 'elevenchars',
    ]);

    // Assert
    $response
        ->assertSessionHasErrors('password')
        ->assertSessionMissing('_old_input.password');

    expect($user->refresh()->password)->toBe($originalPassword)
        ->and(Password::tokenExists($user, $token))->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
});
