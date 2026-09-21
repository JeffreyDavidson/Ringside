<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertAuthenticatedAs;

test('registration screen can be rendered', function () {
    // Arrange
    $registrationUrl = route('register');

    // Act
    $response = $this->get($registrationUrl);

    // Assert
    $response->assertSuccessful();
});

test('a user can register with their account details', function (string $email): void {
    // Arrange
    $registrationData = [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    // Act
    $response = $this->post(route('register'), $registrationData);

    // Assert
    $response->assertRedirect(route('dashboard'));
    $user = User::query()->where('email', 'jeffrey@example.com')->firstOrFail();

    expect($user)
        ->first_name->toBe('Jeffrey')
        ->last_name->toBe('Davidson')
        ->role->toBe(Role::Basic)
        ->and(Hash::check('password', $user->password))->toBeTrue();

    assertAuthenticatedAs($user);
})->with(['jeffrey@example.com', 'Jeffrey@Example.COM']);

test('registration requires valid account details', function () {
    // Arrange
    $registrationData = [
        'first_name' => '',
        'last_name' => '',
        'email' => 'not-an-email',
        'password' => 'secret',
        'password_confirmation' => 'different-secret',
    ];

    // Act
    $response = $this->from(route('register'))
        ->post(route('register'), $registrationData);

    // Assert
    $response
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password'])
        ->assertSessionHasInput('email')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
});

test('registration checks uniqueness after normalizing email', function (): void {
    // Arrange
    User::factory()->create(['email' => 'existing@example.com']);

    // Act
    $response = $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => 'Taylor',
            'last_name' => 'Promoter',
            'email' => 'Existing@Example.COM',
            'password' => 'test-password',
            'password_confirmation' => 'test-password',
        ]);

    // Assert
    $response->assertSessionHasErrors(['email' => __('validation.unique', ['attribute' => 'email'])])
        ->assertSessionHasInput('email', 'Existing@Example.COM');
    expect(User::query()->count())->toBe(1);
});
