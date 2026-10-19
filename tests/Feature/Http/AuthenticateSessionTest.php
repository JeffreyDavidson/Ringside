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

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeaders;
use function Pest\Laravel\withUnencryptedCookies;
use function Pest\Livewire\livewire;

/**
 * Prepare a mounted form snapshot and the browser's update payload without sending the save request.
 *
 * @param  array<string, string>  $updates
 * @return array{components: list<array{snapshot: string, updates: array<string, string>, calls: list<array{method: string, params: array{}}>}>}
 */
function usersFormSavePayload(User $user, array $updates): array
{
    $snapshot = livewire(FormModal::class, ['modelId' => $user->id])
        ->__get('snapshot');

    return ['components' => [[
        'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        'updates' => $updates,
        'calls' => [['method' => 'save', 'params' => []]],
    ]]];
}

/** @param TestResponse<Response> $response */
function rememberCookie(TestResponse $response): Cookie
{
    /** @var Cookie */
    return collect($response->headers->getCookies())
        ->firstOrFail(fn (Cookie $cookie): bool => str_starts_with($cookie->getName(), 'remember_web_'));
}

describe('password and session authentication', function (): void {
    test('an administrator who changes their own password stays signed in on this session', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => false,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $payload = usersFormSavePayload($administrator, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();
        $passwordChange = withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $passwordChange
            ->assertOk();
        auth()->forgetGuards();

        // Act
        $response = get(route('dashboard'));

        // Assert
        $administrator->refresh();

        $response
            ->assertOk();

        assertAuthenticatedAs($administrator);
        expect(Hash::check('replacement-password', $administrator->password))->toBeTrue();
    });

    test('other sessions of an administrator who changes their own password are ended', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => false,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();
        $otherSessionHash = session('password_hash_web');

        $payload = usersFormSavePayload($administrator, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();
        $passwordChange = withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $passwordChange
            ->assertOk();

        // Replay the hash retained by another session before the password change.
        session()->put('password_hash_web', $otherSessionHash);
        auth()->forgetGuards();

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertRedirect(route('login'));

        assertGuest();
    });

    test('an administrator who saves their own account with a blank password keeps their password and session', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => false,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();
        $originalHash = $administrator->password;

        $payload = usersFormSavePayload($administrator, ['form.first_name' => 'Renamed']);
        auth()->forgetGuards();
        $accountSave = withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $accountSave
            ->assertOk();
        auth()->forgetGuards();

        // Act
        $response = get(route('dashboard'));

        // Assert
        $administrator->refresh();

        $response
            ->assertOk();

        assertAuthenticatedAs($administrator);
        expect($administrator->first_name)->toBe('Renamed')
            ->and($administrator->password)->toBe($originalHash)
            ->and(Hash::check('original-password', $administrator->password))->toBeTrue();
    });

    test('an administrator who changes another users password keeps their current session', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => true,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $oldCookie = rememberCookie($login);
        cookie()->unqueue($oldCookie->getName());

        $otherUser = User::factory()
            ->create(['status' => UserStatus::Active]);
        $originalHash = $administrator->password;

        $payload = usersFormSavePayload($otherUser, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();
        $passwordChange = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->withCredentials()
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $passwordChange
            ->assertOk();
        auth()->forgetGuards();

        // Act
        $response = get(route('dashboard'));

        // Assert
        $administrator->refresh();
        $otherUser->refresh();

        $response
            ->assertOk();

        assertAuthenticatedAs($administrator);
        expect($administrator->password)->toBe($originalHash)
            ->and(Hash::check('replacement-password', $otherUser->password))->toBeTrue();
    });
});

describe('remember-me authentication', function (): void {
    test('an administrator who changes their own password gets a remember-me cookie carrying the new hash', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => true,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $oldCookie = rememberCookie($login);
        // Queued login cookies would otherwise be attached to later responses too.
        cookie()->unqueue($oldCookie->getName());

        $payload = usersFormSavePayload($administrator, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();

        // Act
        $response = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->withCredentials()
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);

        // Assert
        $newCookie = rememberCookie($response);
        $administrator->refresh();
        $decrypted = Crypt::decryptString((string) $newCookie->getValue());
        [$id, $token, $hash] = explode('|', CookieValuePrefix::remove($decrypted));

        $response
            ->assertOk();

        expect($id)->toBe((string) $administrator->id)
            ->and($token)->toBe($administrator->getRememberToken())
            ->and($hash)->toBe(auth()->guard()->hashPasswordForCookie($administrator->password))
            ->and(Hash::check('replacement-password', $administrator->password))->toBeTrue();
    });

    test('the new remember-me cookie signs in a session-less request', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => true,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $oldCookie = rememberCookie($login);
        // Queued login cookies would otherwise be attached to later responses too.
        cookie()->unqueue($oldCookie->getName());

        $payload = usersFormSavePayload($administrator, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();
        $passwordChange = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->withCredentials()
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $passwordChange
            ->assertOk();
        $newCookie = rememberCookie($passwordChange);

        // Challenge the cookie without the session that originally authenticated the user.
        session()->flush();
        auth()->forgetGuards();

        // Act
        $response = withUnencryptedCookies([$newCookie->getName() => (string) $newCookie->getValue()])
            ->get(route('dashboard'));

        // Assert
        $response
            ->assertOk();

        assertAuthenticatedAs($administrator);
    });

    test('the old remember-me cookie cannot sign in after a password change', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => true,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $oldCookie = rememberCookie($login);
        // Queued login cookies would otherwise be attached to later responses too.
        cookie()->unqueue($oldCookie->getName());

        $payload = usersFormSavePayload($administrator, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();
        $passwordChange = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->withCredentials()
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $passwordChange
            ->assertOk();

        // Challenge the cookie without the session that originally authenticated the user.
        session()->flush();
        auth()->forgetGuards();

        // Act
        $response = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->get(route('dashboard'));

        // Assert
        $response
            ->assertRedirect(route('login'));

        assertGuest();
    });

    test('an administrator who changes another users password does not replace their remember-me cookie', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => true,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $oldCookie = rememberCookie($login);
        // Queued login cookies would otherwise be attached to later responses too.
        cookie()->unqueue($oldCookie->getName());

        $otherUser = User::factory()
            ->create(['status' => UserStatus::Active]);

        $payload = usersFormSavePayload($otherUser, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();

        // Act
        $response = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->withCredentials()
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);

        // Assert
        $rememberCookies = collect($response->headers->getCookies())
            ->map->getName()
            ->filter(fn (string $name): bool => str_starts_with($name, 'remember_web_'));
        $otherUser->refresh();

        $response
            ->assertOk();

        assertAuthenticatedAs($administrator);
        expect($rememberCookies)->toBeEmpty()
            ->and(Hash::check('replacement-password', $otherUser->password))->toBeTrue();
    });

    test('an administrators remember-me cookie remains usable after they change another users password', function (): void {
        // Arrange
        $administrator = User::factory()
            ->administrator()
            ->create([
                'email' => 'admin@example.com',
                'password' => 'original-password',
                'status' => UserStatus::Active,
            ]);

        $login = post(route('login'), [
            'email' => $administrator->email,
            'password' => 'original-password',
            'remember' => true,
        ]);
        $login
            ->assertRedirect(route('dashboard'));
        $baseline = get(route('dashboard'));
        $baseline
            ->assertOk();

        $oldCookie = rememberCookie($login);
        // Queued login cookies would otherwise be attached to later responses too.
        cookie()->unqueue($oldCookie->getName());

        $otherUser = User::factory()
            ->create(['status' => UserStatus::Active]);

        $payload = usersFormSavePayload($otherUser, [
            'form.password' => 'replacement-password',
            'form.password_confirmation' => 'replacement-password',
        ]);
        auth()->forgetGuards();
        $passwordChange = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->withCredentials()
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(route('default-livewire.update'), $payload);
        $passwordChange
            ->assertOk();

        session()->flush();
        auth()->forgetGuards();

        // Act
        $response = withUnencryptedCookies([$oldCookie->getName() => (string) $oldCookie->getValue()])
            ->get(route('dashboard'));

        // Assert
        $response
            ->assertOk();

        assertAuthenticatedAs($administrator);
    });
});
