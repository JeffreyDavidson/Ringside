<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Livewire\Users\Modals\FormModal;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeaders;
use function Pest\Livewire\livewire;

/**
 * Saves a new password for the user through the users form modal with the request a browser sends, so the
 * web middleware (including AuthenticateSession) runs around the Livewire update.
 *
 * @return TestResponse<Response>
 */
function saveNewPasswordThroughUsersForm(User $user, string $password): TestResponse
{
    $snapshot = livewire(FormModal::class)
        ->call('openModal', $user->id)
        ->set([
            'form.password' => $password,
            'form.password_confirmation' => $password,
        ])
        ->__get('snapshot');

    auth()->forgetGuards();

    return withHeaders(['X-Livewire' => 'true'])
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

function signInAsAdministrator(): User
{
    $administrator = User::factory()->administrator()->create([
        'email' => 'admin@example.com',
        'password' => 'original-password',
        'status' => UserStatus::Active,
    ]);

    post(route('login'), ['email' => 'admin@example.com', 'password' => 'original-password']);
    get(route('dashboard'))->assertSuccessful();

    return $administrator;
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
