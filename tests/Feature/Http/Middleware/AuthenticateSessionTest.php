<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Livewire\Users\Modals\FormModal;
use App\Models\Users\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withUnencryptedCookies;
use function Pest\Livewire\livewire;

/**
 * Saves the users form modal for the user with the request a browser sends, so the web middleware (including
 * AuthenticateSession) runs around the Livewire update.
 *
 * @param  array<string, string>  $formState  The form fields to change before saving, keyed by property path
 * @param  ?Cookie  $recaller  The encrypted remember-me cookie the browser sends along, when it has one
 * @return TestResponse<Response>
 */
function saveUsersFormThroughHttp(User $user, array $formState, ?Cookie $recaller = null): TestResponse
{
    $snapshot = livewire(FormModal::class)
        ->call('openModal', $user->id)
        ->set($formState)
        ->__get('snapshot');

    auth()->forgetGuards();

    return withUnencryptedCookies(
        $recaller instanceof Cookie ? [$recaller->getName() => (string) $recaller->getValue()] : [],
    )->withCredentials()->withHeaders(['X-Livewire' => 'true'])
        ->postJson(route('default-livewire.update'), [
            'components' => [
                [
                    'snapshot' => json_encode($snapshot),
                    'updates' => [],
                    'calls' => [['method' => 'save', 'params' => []]],
                ],
            ],
        ]);
}

/**
 * Saves a new password for the user through the users form modal.
 *
 * @param  ?Cookie  $recaller  The encrypted remember-me cookie the browser sends along, when it has one
 * @return TestResponse<Response>
 */
function saveNewPasswordThroughUsersForm(User $user, string $password, ?Cookie $recaller = null): TestResponse
{
    return saveUsersFormThroughHttp($user, [
        'form.password' => $password,
        'form.password_confirmation' => $password,
    ], $recaller);
}

function signInAsAdministrator(): User
{
    return signIn()[0];
}

/**
 * Signs in an administrator with "remember me" and returns it with the encrypted remember-me cookie the browser received.
 *
 * @return array{0: User, 1: Cookie}
 */
function signInAsRememberedAdministrator(): array
{
    [$administrator, $response] = signIn(remember: true);

    $cookie = rememberCookie($response);

    // The shared cookie jar would otherwise attach the login's cookie to every later response in this test.
    cookie()->unqueue($cookie->getName());

    return [$administrator, $cookie];
}

/**
 * @return array{0: User, 1: TestResponse<Response>}
 */
function signIn(bool $remember = false): array
{
    $administrator = User::factory()->administrator()->create([
        'email' => 'admin@example.com',
        'password' => 'original-password',
        'status' => UserStatus::Active,
    ]);

    $response = post(route('login'), [
        'email' => 'admin@example.com',
        'password' => 'original-password',
        'remember' => $remember,
    ]);
    get(route('dashboard'))->assertSuccessful();

    return [$administrator, $response];
}

/**
 * @param  TestResponse<Response>  $response
 */
function rememberCookie(TestResponse $response): Cookie
{
    /** @var Cookie */
    return collect($response->headers->getCookies())
        ->firstOrFail(fn (Cookie $cookie): bool => str_starts_with($cookie->getName(), 'remember_web_'));
}

test('an administrator who changes their own password stays signed in on this session', function (): void {
    // Arrange
    $administrator = signInAsAdministrator();

    // Act
    $response = saveNewPasswordThroughUsersForm($administrator, 'replacement-password');

    // Assert
    $response->assertOk();
    auth()->forgetGuards();

    get(route('dashboard'))->assertSuccessful();
    expect(Hash::check('replacement-password', $administrator->refresh()->password))->toBeTrue();
});

test('other sessions of an administrator who changes their own password are ended', function (): void {
    // Arrange
    $administrator = signInAsAdministrator();
    $otherSessionHash = session('password_hash_web');

    // Act
    saveNewPasswordThroughUsersForm($administrator, 'replacement-password')->assertOk();

    // Assert
    session()->put('password_hash_web', $otherSessionHash);
    auth()->forgetGuards();

    get(route('dashboard'))->assertRedirect(route('login'));
});

/**
 * Sends a request without a session, the way a browser does after its session ended but the remember-me cookie remains.
 *
 * @return TestResponse<Response>
 */
function visitDashboardWithOnlyCookie(Cookie $cookie): TestResponse
{
    session()->flush();
    auth()->forgetGuards();

    return withUnencryptedCookies([$cookie->getName() => (string) $cookie->getValue()])->get(route('dashboard'));
}

test('an administrator who changes their own password gets a remember-me cookie carrying the new hash', function (): void {
    // Arrange
    [$administrator, $oldCookie] = signInAsRememberedAdministrator();

    // Act
    $response = saveNewPasswordThroughUsersForm(
        $administrator,
        'replacement-password',
        $oldCookie,
    );

    // Assert
    $newCookie = rememberCookie($response);
    $administrator->refresh();
    [$id, $token, $hash] = explode('|', CookieValuePrefix::remove(Crypt::decryptString((string) $newCookie->getValue())));

    expect($id)->toBe((string) $administrator->id)
        ->and($token)->toBe($administrator->getRememberToken())
        ->and($hash)->toBe(auth()->guard()->hashPasswordForCookie($administrator->password));
});

test('the new remember-me cookie signs in a session-less request and the old one does not', function (): void {
    // Arrange
    [$administrator, $oldCookie] = signInAsRememberedAdministrator();
    $newCookie = rememberCookie(saveNewPasswordThroughUsersForm(
        $administrator,
        'replacement-password',
        $oldCookie,
    ));

    // Act
    $withNewCookie = visitDashboardWithOnlyCookie($newCookie);
    $withOldCookie = visitDashboardWithOnlyCookie($oldCookie);

    // Assert
    $withNewCookie->assertSuccessful();
    $withOldCookie->assertRedirect(route('login'));
});

test('an administrator who changes another user\'s password keeps their session and remember-me cookie', function (): void {
    // Arrange
    [, $oldCookie] = signInAsRememberedAdministrator();
    $otherUser = User::factory()->create(['status' => UserStatus::Active]);

    // Act
    $response = saveNewPasswordThroughUsersForm(
        $otherUser,
        'replacement-password',
        $oldCookie,
    );

    // Assert
    $response->assertOk();
    expect(collect($response->headers->getCookies())->map->getName()->filter(fn (string $name): bool => str_starts_with($name, 'remember_web_')))->toBeEmpty();
    auth()->forgetGuards();

    get(route('dashboard'))->assertSuccessful();
    visitDashboardWithOnlyCookie($oldCookie)->assertSuccessful();
});

test('an administrator who saves their own account with a blank password keeps their password and session', function (): void {
    // Arrange
    $administrator = signInAsAdministrator();
    $originalHash = $administrator->password;

    // Act
    $response = saveUsersFormThroughHttp($administrator, ['form.first_name' => 'Renamed']);

    // Assert
    $response->assertOk();
    auth()->forgetGuards();

    get(route('dashboard'))->assertSuccessful();
    $administrator->refresh();
    expect($administrator->first_name)->toBe('Renamed')
        ->and($administrator->password)->toBe($originalHash)
        ->and(Hash::check('original-password', $administrator->password))->toBeTrue();
});
