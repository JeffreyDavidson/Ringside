<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Support\Auth\CaseInsensitiveEmailUserProvider;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\DB;

function emailProvider(): UserProvider
{
    return auth()->createUserProvider('users') ?? throw new LogicException('The users provider is not configured.');
}

test('the users auth provider ignores email case', function () {
    expect(emailProvider())->toBeInstanceOf(CaseInsensitiveEmailUserProvider::class);
});

test('it finds a user ignoring the case and spacing of the email', function () {
    // Arrange
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['email' => 'Legacy.User@Example.Test']);

    // Act
    $found = emailProvider()->retrieveByCredentials(['email' => '  legacy.user@example.test ', 'password' => 'secret']);

    // Assert
    expect($found?->getAuthIdentifier())->toBe($user->id);
});

test('it never treats wildcards in the email as patterns', function (string $input) {
    // Arrange
    User::factory()->create(['email' => 'someone@example.test']);

    // Act
    $found = emailProvider()->retrieveByCredentials(['email' => $input, 'password' => 'secret']);

    // Assert
    expect($found)->toBeNull();
})->with([
    'percent' => '%@example.test',
    'underscore' => 'someon_@example.test',
]);

test('it falls back to the default lookup when there is no email', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $found = emailProvider()->retrieveByCredentials(['id' => $user->id]);

    // Assert
    expect($found?->getAuthIdentifier())->toBe($user->id);
});
