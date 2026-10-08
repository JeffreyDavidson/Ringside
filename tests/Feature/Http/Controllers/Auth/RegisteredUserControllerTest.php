<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('registration screen can be rendered', function () {
    // Arrange
    $registrationUrl = route('register');

    // Act
    $response = get($registrationUrl);

    // Assert
    $response->assertSuccessful();
});

test('a user can register with their account details', function (string $email): void {
    // Arrange
    $registrationData = [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'email' => $email,
        'password' => 'password-12345',
        'password_confirmation' => 'password-12345',
    ];

    // Act
    $response = post(route('register'), $registrationData);

    // Assert
    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_pending'));
    $user = User::query()->where('email', 'jeffrey@example.com')->firstOrFail();

    expect($user)
        ->first_name->toBe('Jeffrey')
        ->last_name->toBe('Davidson')
        ->role->toBe(Role::Basic)
        ->status->toBe(UserStatus::Unverified)
        ->and(Hash::check('password-12345', $user->password))->toBeTrue();

    assertGuest();
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
    $response = from(route('register'))
        ->post(route('register'), $registrationData);

    // Assert
    $response
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password'])
        ->assertSessionHasInput('email')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
});

test('registration answers the same for a taken and an unknown email', function (string $takenEmail): void {
    // Arrange
    User::factory()->create(['email' => 'existing@example.com']);
    $registration = fn (string $email): array => [
        'first_name' => 'Taylor',
        'last_name' => 'Promoter',
        'email' => $email,
        'password' => 'test-password-123',
        'password_confirmation' => 'test-password-123',
    ];

    // Act
    $taken = from(route('register'))
        ->post(route('register'), $registration($takenEmail));
    $unknown = from(route('register'))
        ->post(route('register'), $registration('unknown@example.com'));

    // Assert
    $taken->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_pending'))
        ->assertSessionHasNoErrors();
    $unknown->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_pending'))
        ->assertSessionHasNoErrors();
    expect(User::query()->count())->toBe(2)
        ->and(User::query()->where('email', 'existing@example.com')->count())->toBe(1);
})->with([
    'the same email' => 'existing@example.com',
    'an email that differs by case' => 'Existing@Example.COM',
]);

test('registration creates nothing for the email of a deleted user', function (): void {
    // Arrange
    User::factory()->create(['email' => 'gone@example.com'])->delete();

    // Act
    $response = post(route('register'), [
        'first_name' => 'Taylor',
        'last_name' => 'Promoter',
        'email' => 'gone@example.com',
        'password' => 'test-password-123',
        'password_confirmation' => 'test-password-123',
    ]);

    // Assert
    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_pending'));
    expect(User::query()->withTrashed()->count())->toBe(1);
});

test('registration still validates the other fields for a taken email', function (): void {
    // Arrange
    User::factory()->create(['email' => 'existing@example.com']);

    // Act
    $response = from(route('register'))
        ->post(route('register'), [
            'first_name' => '',
            'last_name' => 'Promoter',
            'email' => 'existing@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

    // Assert
    $response->assertRedirect(route('register'))
        ->assertSessionHasErrors(['first_name', 'password'])
        ->assertSessionDoesntHaveErrors('email');
});

test('registration answers as pending when the database rejects a duplicate email', function (): void {
    // Arrange
    User::creating(function (): void {
        throw new UniqueConstraintViolationException('testing', 'insert into users', [], new Exception('duplicate'));
    });
    Event::fake([Registered::class]);

    // Act
    $response = post(route('register'), [
        'first_name' => 'Taylor',
        'last_name' => 'Promoter',
        'email' => 'new@example.com',
        'password' => 'test-password-123',
        'password_confirmation' => 'test-password-123',
    ]);

    // Assert
    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_pending'))
        ->assertSessionHasNoErrors();
    Event::assertNotDispatched(Registered::class);
    expect(User::query()->count())->toBe(0);
});
