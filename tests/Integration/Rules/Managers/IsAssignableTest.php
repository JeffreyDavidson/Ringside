<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Rules\Managers\IsAssignable;
use Illuminate\Support\Facades\Validator;

it('accepts an employed manager', function (): void {
    // Arrange
    $manager = Manager::factory()
        ->employed()
        ->create();

    // Act
    $validator = Validator::make(['manager' => $manager->id], ['manager' => [new IsAssignable]]);

    // Assert
    expect($validator->passes())->toBeTrue();
});

it('rejects a manager who is not available, with the reason', function (string $state, string $message): void {
    // Arrange
    $manager = Manager::factory()
        ->{$state}()
        ->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);

    // Act
    $validator = Validator::make(['manager' => $manager->id], ['manager' => [new IsAssignable]]);

    // Assert
    expect($validator->errors()->first('manager'))->toBe($message);
})->with([
    'never employed' => ['unemployed', 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'released' => ['released', 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'retired' => ['retired', 'Manager Bobby Heenan is retired and cannot be assigned.'],
    'suspended' => ['suspended', 'Manager Bobby Heenan is suspended and cannot be assigned.'],
    'injured' => ['injured', 'Manager Bobby Heenan is injured and cannot be assigned.'],
]);

it('accepts a manager who is already assigned even when no longer eligible', function (): void {
    // Arrange
    $manager = Manager::factory()
        ->released()
        ->create();

    // Act
    $validator = Validator::make(['manager' => $manager->id], ['manager' => [new IsAssignable([$manager->id])]]);

    // Assert
    expect($validator->passes())->toBeTrue();
});

it('rejects an identifier that is not a scalar or not a manager', function (mixed $value): void {
    // Arrange
    $rules = ['manager' => [new IsAssignable]];

    // Act
    $validator = Validator::make(['manager' => $value], $rules);

    // Assert
    expect($validator->errors()->first('manager'))->toBe('The selected manager is invalid.');
})->with([
    'array' => [[1]],
    'boolean' => [true],
    'unknown manager' => [999_999],
]);
