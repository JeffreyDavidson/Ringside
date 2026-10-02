<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

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
    Notification::fake();
    $user = User::factory()->create();
    post(route('password.email'), ['email' => $user->email]);

    // Act
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $response = post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'elevenchars',
            'password_confirmation' => 'elevenchars',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');

        return true;
    });
});
