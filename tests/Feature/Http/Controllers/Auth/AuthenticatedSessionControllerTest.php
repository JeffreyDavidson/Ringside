<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

describe('create', function (): void {
    test('login screen can be rendered', function () {
        // Arrange
        $loginUrl = route('login');

        // Act
        $response = get($loginUrl);

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('auth.login');
    });
});

describe('store', function (): void {
    test('users can authenticate using the login screen', function (string $email) {
        // Arrange
        $user = User::factory()
            ->unverified()
            ->create([
                'email' => 'promoter@example.com',
                'status' => UserStatus::Active,
            ]);
        $credentials = [
            'email' => $email,
            'password' => 'secret',
        ];

        // Act
        $response = post(route('login'), $credentials);

        // Assert
        $response
            ->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticatedAs($user);
    })->with([
        'lowercase email' => 'promoter@example.com',
        'mixed-case email' => 'Promoter@Example.com',
        'surrounding whitespace' => '  Promoter@Example.com  ',
    ]);

    test('users can not authenticate with invalid password', function () {
        // Arrange
        $user = User::factory()
            ->create(['status' => UserStatus::Active]);
        $credentials = [
            'email' => $user->email,
            'password' => 'wrong-password',
        ];

        // Act
        $response = from(route('login'))->post(route('login'), $credentials);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password');

        assertGuest();
    });

    test('only active users can authenticate', function (UserStatus $status) {
        // Arrange
        $user = User::factory()
            ->create(['status' => $status]);

        $requestData = [
            'email' => $user->email,
            'password' => 'secret',
        ];

        // Act
        $response = from(route('login'))->post(route('login'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        assertGuest();
    })->with([
        'unverified' => UserStatus::Unverified,
        'inactive' => UserStatus::Inactive,
    ]);

    test('deactivated users are logged out on their next request', function () {
        // Arrange
        $user = User::factory()
            ->create(['status' => UserStatus::Active]);

        actingAs($user);
        $user->update(['status' => UserStatus::Inactive]);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_inactive'));

        assertGuest();
    });

    test('login requires credentials', function () {
        // Arrange
        $credentials = [];

        // Act
        $response = post(route('login'), $credentials);

        // Assert
        $response
            ->assertSessionHasErrors(['email', 'password']);

        assertGuest();
    });

    test('the sixth failed login within the throttle window is locked out', function () {
        // Arrange
        Event::fake([Lockout::class]);
        freezeTime();
        $user = User::factory()
            ->create(['status' => UserStatus::Active]);
        $credentials = [
            'email' => $user->email,
            'password' => 'secret',
        ];
        foreach (range(1, 5) as $attempt) {
            from(route('login'))->post(route('login'), [
                ...$credentials,
                'password' => 'wrong-password',
            ]);
        }

        // Act
        $response = from(route('login'))->post(route('login'), $credentials);

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => trans('auth.throttle', [
                'seconds' => 60,
                'minutes' => 1,
            ])]);

        Event::assertDispatched(Lockout::class, fn (Lockout $event): bool => $event->request->input('email') === $user->email);
        Event::assertDispatchedOnce(Lockout::class);
        assertGuest();
    });

    test('failed logins under the throttle limit do not lock the account', function () {
        // Arrange
        Event::fake([Lockout::class]);
        $user = User::factory()
            ->create(['status' => UserStatus::Active]);
        foreach (range(1, 4) as $attempt) {
            from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $requestData = [
            'email' => $user->email,
            'password' => 'secret',
        ];

        // Act
        $response = post(route('login'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('dashboard', absolute: false));

        Event::assertNotDispatched(Lockout::class);
        assertAuthenticatedAs($user);
    });

    test('users can log in again once the throttle window has passed', function () {
        // Arrange
        freezeTime();
        $user = User::factory()
            ->create(['status' => UserStatus::Active]);
        foreach (range(1, 5) as $attempt) {
            from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }
        travel(61)->seconds();

        $requestData = [
            'email' => $user->email,
            'password' => 'secret',
        ];

        // Act
        $response = post(route('login'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticatedAs($user);
    });

    test('a legacy user stored with a mixed-case email can still sign in', function (string $typedEmail) {
        // Arrange
        $user = User::factory()
            ->create(['status' => UserStatus::Active]);
        DB::table('users')
            ->where('id', $user->id)
            ->update(['email' => 'Legacy.Promoter@Example.com']);

        $requestData = [
            'email' => $typedEmail,
            'password' => 'secret',
        ];

        // Act
        $response = post(route('login'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticatedAs($user);
    })->with([
        'lowercase' => 'legacy.promoter@example.com',
        'exact stored case' => 'Legacy.Promoter@Example.com',
    ]);
});

describe('destroy', function (): void {
    test('authenticated users can log out', function () {
        // Arrange
        actingAs(administrator());
        session()->put('private_session_marker', 'must-disappear');
        $sessionId = session()->getId();
        $csrfToken = session()->token();

        // Act
        $response = post(route('logout'));

        // Assert
        $response
            ->assertRedirect(route('login'))
            ->assertSessionMissing('private_session_marker');

        expect(session()->getId())->not->toBe($sessionId)
            ->and(session()->token())->not->toBe($csrfToken);
        assertGuest();
    });
});

describe('email preparation', function (): void {
    test('null and non-string email values remain validation failures', function (mixed $email): void {
        // Arrange
        $requestData = [
            'password' => 'password-12345',
            'email' => $email,
        ];

        // Act
        $response = post(route('login'), $requestData);

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

test('a refused login keeps the entered email case without retaining the password', function (): void {
    // Arrange
    $user = User::factory()
        ->create([
            'email' => 'promoter@example.com',
            'status' => UserStatus::Active,
        ]);

    $requestData = [
        'email' => '  Promoter@Example.COM  ',
        'password' => 'wrong-password',
    ];

    // Act
    $response = from(route('login'))
        ->post(route('login'), $requestData);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email', 'Promoter@Example.COM')
        ->assertSessionMissing('_old_input.password');

    assertGuest();
    expect($user->refresh()->email)->toBe('promoter@example.com');
});

describe('remember input', function (): void {
    test('non-empty remember values retain the existing remember cookie behavior', function (mixed $remember): void {
        // Arrange
        $user = User::factory()
            ->create([
                'email' => 'promoter@example.com',
                'password' => 'password-12345',
                'status' => UserStatus::Active,
            ]);
        $cookieName = Auth::getRecallerName();

        $requestData = [
            'email' => $user->email,
            'password' => 'password-12345',
            'remember' => $remember,
        ];

        // Act
        $response = post(route('login'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasNoErrors()
            ->assertCookie($cookieName);

        assertAuthenticatedAs($user);
    })->with([
        'checkbox value' => 'on',
        'zero string' => '0',
        'false boolean' => false,
    ]);

    test('empty remember values do not issue a remember cookie', function (mixed $remember): void {
        // Arrange
        $user = User::factory()
            ->create([
                'email' => 'promoter@example.com',
                'password' => 'password-12345',
                'status' => UserStatus::Active,
            ]);
        $cookieName = Auth::getRecallerName();

        $requestData = [
            'email' => $user->email,
            'password' => 'password-12345',
            'remember' => $remember,
        ];

        // Act
        $response = post(route('login'), $requestData);

        // Assert
        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasNoErrors()
            ->assertCookieMissing($cookieName);

        assertAuthenticatedAs($user);
    })->with([
        'null' => null,
        'empty string' => '',
    ]);
});
