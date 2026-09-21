<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\ValueObjects\PhoneNumber;

test('it casts stored digits to a phone number', function () {
    $user = User::factory()->create(['phone_number' => '1234567890']);

    expect($user->phone_number)->toEqual(new PhoneNumber('1234567890'));
});

test('it normalizes phone number values for storage', function () {
    $user = User::factory()->create(['phone_number' => new PhoneNumber('(123) 456-7890')]);

    expect($user->getRawOriginal('phone_number'))->toBe('1234567890')
        ->and($user->toArray()['phone_number'])->toBe('1234567890');
});

test('it preserves null phone numbers', function () {
    $user = User::factory()->create(['phone_number' => null]);

    expect($user->phone_number)->toBeNull();
});
