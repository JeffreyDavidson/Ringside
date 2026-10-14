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

describe('create', function (): void {
    test('registration screen can be rendered', function () {
        // Arrange
        $registrationUrl = route('register');

        // Act
        $response = get($registrationUrl);

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('auth.register');
    });
});

describe('store', function (): void {
    test('a user can register with their account details', function (string $email): void {
        // Arrange
        $registrationData = [
            'first_name' => 'Jeffrey',
            'last_name' => 'Davidson',
            'email' => $email,
            'password' => 'password-12345',
            'password_confirmation' => 'password-12345',
            'role' => Role::Administrator->value,
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(),
        ];

        Event::fake([Registered::class]);

        // Act
        $response = post(route('register'), $registrationData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_pending'));

        $user = User::query()
            ->where('email', 'jeffrey@example.com')
            ->firstOrFail();

        expect($user)
            ->first_name->toBe('Jeffrey')
            ->last_name->toBe('Davidson')
            ->role->toBe(Role::Basic)
            ->status->toBe(UserStatus::Unverified)
            ->email_verified_at->toBeNull()
            ->and(Hash::check('password-12345', $user->password))
            ->toBeTrue();

        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user instanceof User && $event->user->is($user));
        Event::assertDispatchedOnce(Registered::class);
        assertGuest();
    })->with([
        'lowercase email' => 'jeffrey@example.com',
        'mixed-case email' => 'Jeffrey@Example.COM',
    ]);

    test('registration requires valid account details', function () {
        // Arrange
        $registrationData = [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'password' => 'secret',
            'password_confirmation' => 'different-secret',
        ];

        Event::fake([Registered::class]);

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

        expect(User::query()
            ->count())->toBe(0);
        Event::assertNotDispatched(Registered::class);
    });

    test('a taken email gets the pending response without changing the account', function (string $takenEmail): void {
        // Arrange
        $existing = User::factory()
            ->create(['email' => 'existing@example.com']);
        $original = $existing->refresh()
            ->getAttributes();
        $registrationData = [
            'first_name' => 'Taylor',
            'last_name' => 'Promoter',
            'email' => $takenEmail,
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
        ];

        Event::fake([Registered::class]);

        // Act
        $response = from(route('register'))->post(route('register'), $registrationData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_pending'))
            ->assertSessionHasNoErrors();

        expect(User::query()
            ->count())->toBe(1)
            ->and($existing->refresh()
                ->getAttributes())
            ->toBe($original);
        Event::assertNotDispatched(Registered::class);
        assertGuest();
    })->with([
        'the same email' => 'existing@example.com',
        'an email that differs by case' => 'Existing@Example.COM',
    ]);

    test('an unknown email gets the same pending response and creates its own account', function (): void {
        // Arrange
        $existing = User::factory()
            ->create(['email' => 'existing@example.com']);
        $original = $existing->refresh()
            ->getAttributes();
        $registrationData = [
            'first_name' => 'Taylor',
            'last_name' => 'Promoter',
            'email' => 'unknown@example.com',
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
        ];

        Event::fake([Registered::class]);

        // Act
        $response = from(route('register'))->post(route('register'), $registrationData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_pending'))
            ->assertSessionHasNoErrors();

        $created = User::query()
            ->where('email', 'unknown@example.com')
            ->firstOrFail();
        expect(User::query()
            ->count())->toBe(2)
            ->and(User::query()
                ->where('email', 'existing@example.com')
                ->count())
            ->toBe(1)
            ->and($existing->refresh()
                ->getAttributes())
            ->toBe($original);
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user instanceof User && $event->user->is($created));
        Event::assertDispatchedOnce(Registered::class);
        Event::assertNotDispatched(Registered::class, fn (Registered $event): bool => $event->user instanceof User && $event->user->is($existing));
        assertGuest();
    });

    test('registration creates nothing for the email of a deleted user', function (): void {
        // Arrange
        User::factory()
            ->create(['email' => 'gone@example.com'])
            ->delete();

        Event::fake([Registered::class]);

        $requestData = [
            'first_name' => 'Taylor',
            'last_name' => 'Promoter',
            'email' => 'gone@example.com',
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
        ];

        // Act
        $response = post(route('register'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_pending'));

        expect(User::query()
            ->withTrashed()
            ->count())->toBe(1);
        Event::assertNotDispatched(Registered::class);
    });

    test('registration still validates the other fields for a taken email', function (): void {
        // Arrange
        User::factory()
            ->create(['email' => 'existing@example.com']);

        Event::fake([Registered::class]);

        $requestData = [
            'first_name' => '',
            'last_name' => 'Promoter',
            'email' => 'existing@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        // Act
        $response = from(route('register'))
            ->post(route('register'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['first_name', 'password'])
            ->assertSessionDoesntHaveErrors('email');

        expect(User::query()
            ->count())->toBe(1);
        Event::assertNotDispatched(Registered::class);
    });

    test('registration answers as pending when the database rejects a duplicate email', function (): void {
        // Arrange
        User::creating(function (): void {
            throw new UniqueConstraintViolationException('testing', 'insert into users', [], new Exception('duplicate'));
        });

        Event::fake([Registered::class]);

        $requestData = [
            'first_name' => 'Taylor',
            'last_name' => 'Promoter',
            'email' => 'new@example.com',
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
        ];

        // Act
        $response = post(route('register'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_pending'))
            ->assertSessionHasNoErrors();

        Event::assertNotDispatched(Registered::class);
        expect(User::query()
            ->count())->toBe(0);
    });
});
