<?php

declare(strict_types=1);

use App\Actions\Users\UpdateAction;
use App\Data\Users\UserData;
use App\Enums\Users\Role;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;

test('it updates a user', function () {
    $user = User::factory()->create();

    $updatedUser = resolve(UpdateAction::class)->handle(
        $user,
        new UserData('Updated', 'User', 'updated@example.com', Role::Administrator, null),
    );

    expect($updatedUser->first_name)->toBe('Updated')
        ->and($updatedUser->last_name)->toBe('User')
        ->and($updatedUser->email)->toBe('updated@example.com')
        ->and($updatedUser->role)->toBe(Role::Administrator);

    $this->assertDatabaseHas('users', [
        'id' => $user->getKey(),
        'email' => 'updated@example.com',
        'role' => Role::Administrator->value,
    ]);
});

test('it updates a user password', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    resolve(UpdateAction::class)->handle(
        $user,
        new UserData('Updated', 'User', $user->email, $user->role, 'new-password'),
    );

    $user->refresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('new-password');
});

test('it updates using the current persisted user state', function () {
    $user = User::factory()->create([
        'first_name' => 'Original',
        'last_name' => 'Name',
    ]);
    $staleUser = $user->replicate(['id']);
    $staleUser->id = $user->id;
    $staleUser->exists = true;

    $updatedUser = resolve(UpdateAction::class)->handle(
        $staleUser,
        new UserData('Updated', 'From Stale State', $user->email, $user->role, null),
    );

    $persistedUser = User::query()
        ->whereKey($user->getKey())
        ->firstOrFail();

    expect($updatedUser->getKey())->toBe($user->getKey())
        ->and($persistedUser->last_name)->toBe('From Stale State');
});
