<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Rules\Users\UniqueEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

function emailValidationPasses(string $email, ?int $ignoreUserId = null): bool
{
    return Validator::make(['email' => $email], ['email' => [new UniqueEmail($ignoreUserId)]])->passes();
}

test('an email differing only by case or whitespace is rejected', function (string $typed) {
    // Arrange
    User::factory()->create(['email' => 'foo@example.com']);

    // Act
    $passes = emailValidationPasses($typed);

    // Assert
    expect($passes)->toBeFalse();
})->with([
    'upper case' => 'FOO@EXAMPLE.COM',
    'mixed case' => 'Foo@Example.com',
    'surrounding whitespace' => ' foo@example.com ',
]);

test('a legacy mixed-case stored email blocks a lowercase duplicate', function () {
    // Arrange
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['email' => 'Foo@Example.com']);

    // Act
    $passes = emailValidationPasses('foo@example.com');

    // Assert
    expect($passes)->toBeFalse();
});

test('a soft-deleted user still reserves the email', function () {
    // Arrange
    User::factory()->create(['email' => 'foo@example.com'])->delete();

    // Act
    $passes = emailValidationPasses('Foo@example.com');

    // Assert
    expect($passes)->toBeFalse();
});

test('a user may keep their own email in a different case', function () {
    // Arrange
    $user = User::factory()->create(['email' => 'foo@example.com']);

    // Act
    $passes = emailValidationPasses('Foo@Example.com', $user->id);

    // Assert
    expect($passes)->toBeTrue();
});

test('an unused email and a non-string value pass the rule itself', function () {
    // Arrange
    User::factory()->create(['email' => 'foo@example.com']);

    // Act
    $unused = emailValidationPasses('other@example.com');
    $nonString = Validator::make(['email' => ['x']], ['email' => [new UniqueEmail]])->passes();

    // Assert
    expect($unused)->toBeTrue()
        ->and($nonString)->toBeTrue();
});

test('LIKE wildcards in the typed email never match a different stored email', function (string $stored, string $typed) {
    // Arrange
    User::factory()->create(['email' => $stored]);

    // Act
    $passes = emailValidationPasses($typed);

    // Assert
    expect($passes)->toBeTrue();
})->with([
    'underscore against any one character' => ['fooXbar@example.com', 'foo_bar@example.com'],
    'percent against any run of characters' => ['foo.long.name@example.com', 'foo%@example.com'],
]);

test('the failure message uses the standard unique wording', function () {
    // Arrange
    User::factory()->create(['email' => 'foo@example.com']);

    // Act
    $message = Validator::make(['email' => 'FOO@example.com'], ['email' => [new UniqueEmail]])->errors()->first('email');

    // Assert
    expect($message)->toBe('The email has already been taken.');
});
