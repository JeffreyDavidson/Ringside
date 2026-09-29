<?php

declare(strict_types=1);

use App\Actions\Users\CreateAction;
use App\Data\Users\UserData;
use App\Enums\Users\Role;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;

test('it creates a user with the given attributes and a hashed password', function () {
    $data = new UserData('Ada', 'Lovelace', 'ada@example.com', Role::Basic, 'secret-password-1');

    $user = resolve(CreateAction::class)->handle($data);

    expect($user->exists)->toBeTrue()
        ->and($user->first_name)->toBe('Ada')
        ->and($user->last_name)->toBe('Lovelace')
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->role)->toBe(Role::Basic)
        ->and(Hash::check('secret-password-1', $user->password))->toBeTrue();
});

test('it refuses to create a user without a password', function () {
    $data = new UserData('Ada', 'Lovelace', 'ada@example.com', Role::Basic, null);

    expect(fn () => resolve(CreateAction::class)->handle($data))
        ->toThrow(InvalidArgumentException::class, 'A password is required to create a user.')
        ->and(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});
