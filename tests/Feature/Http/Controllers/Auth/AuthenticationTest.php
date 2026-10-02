<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;

test('login screen can be rendered', function () {
    // Arrange
    $loginUrl = route('login');

    // Act
    $response = get($loginUrl);

    // Assert
    $response->assertSuccessful();
});

test('users can authenticate using the login screen', function (string $email) {
    // Arrange
    User::factory()->create([
        'email' => 'promoter@example.com',
        'email_verified_at' => null,
        'status' => UserStatus::Active,
    ]);
    $credentials = [
        'email' => $email,
        'password' => 'secret',
    ];

    // Act
    $response = post(route('login'), $credentials);

    // Assert
    $response->assertRedirect(route('dashboard', absolute: false));
    assertAuthenticated();
})->with(['promoter@example.com', 'Promoter@Example.com']);

test('users can not authenticate with invalid password', function () {
    // Arrange
    $user = User::factory()->create();
    $credentials = [
        'email' => $user->email,
        'password' => 'wrong-password',
    ];

    // Act
    $response = from(route('login'))->post(route('login'), $credentials);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    assertGuest();
});

test('only active users can authenticate', function (UserStatus $status) {
    // Arrange
    $user = User::factory()->create(['status' => $status]);

    // Act
    $response = from(route('login'))->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret',
    ]);

    // Assert
    $response->assertRedirect(route('login'))->assertSessionHasErrors('email');
    assertGuest();
})->with([
    'unverified' => UserStatus::Unverified,
    'inactive' => UserStatus::Inactive,
]);

test('deactivated users are logged out on their next request', function () {
    // Arrange
    $user = User::factory()->create(['status' => UserStatus::Active]);
    actingAs($user);
    $user->update(['status' => UserStatus::Inactive]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_inactive'));
    assertGuest();
});

test('login requires credentials', function () {
    // Arrange
    $credentials = [];

    // Act
    $response = post(route('login'), $credentials);

    // Assert
    $response->assertSessionHasErrors(['email', 'password']);
    assertGuest();
});

test('authenticated users can log out', function () {
    // Arrange
    actingAs(administrator());

    // Act
    $response = post(route('logout'));

    // Assert
    $response->assertRedirect(route('login'));
    assertGuest();
});

test('the sixth failed login within the throttle window is locked out', function () {
    // Arrange
    Event::fake([Lockout::class]);
    freezeTime();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $credentials = ['email' => $user->email, 'password' => 'secret'];
    foreach (range(1, 5) as $attempt) {
        from(route('login'))->post(route('login'), [...$credentials, 'password' => 'wrong-password']);
    }

    // Act
    $response = from(route('login'))->post(route('login'), $credentials);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => trans('auth.throttle', ['seconds' => 60, 'minutes' => 1])]);
    Event::assertDispatched(Lockout::class);
    assertGuest();
});

test('failed logins under the throttle limit do not lock the account', function () {
    // Arrange
    Event::fake([Lockout::class]);
    $user = User::factory()->create(['status' => UserStatus::Active]);
    foreach (range(1, 4) as $attempt) {
        from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    // Act
    $response = post(route('login'), ['email' => $user->email, 'password' => 'secret']);

    // Assert
    $response->assertRedirect(route('dashboard', absolute: false));
    Event::assertNotDispatched(Lockout::class);
    assertAuthenticated();
});

test('users can log in again once the throttle window has passed', function () {
    // Arrange
    freezeTime();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    foreach (range(1, 5) as $attempt) {
        from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    }
    travel(61)->seconds();

    // Act
    $response = post(route('login'), ['email' => $user->email, 'password' => 'secret']);

    // Assert
    $response->assertRedirect(route('dashboard', absolute: false));
    assertAuthenticated();
});

test('a legacy user stored with a mixed-case email can still sign in', function (string $typedEmail) {
    // Arrange
    $user = User::factory()->create(['status' => UserStatus::Active]);
    DB::table('users')->where('id', $user->id)->update(['email' => 'Legacy.Promoter@Example.com']);

    // Act
    $response = post(route('login'), ['email' => $typedEmail, 'password' => 'secret']);

    // Assert
    $response->assertRedirect(route('dashboard', absolute: false));
    assertAuthenticated();
})->with([
    'lowercase' => 'legacy.promoter@example.com',
    'exact stored case' => 'Legacy.Promoter@Example.com',
]);
