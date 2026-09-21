<?php

declare(strict_types=1);

use App\ValueObjects\PhoneNumber;

test('it normalizes and formats a phone number', function () {
    $phoneNumber = new PhoneNumber('(123) 456-7890');

    expect($phoneNumber->toDigits())->toBe('1234567890')
        ->and($phoneNumber->formatted())->toBe('(123) 456-7890')
        ->and((string) $phoneNumber)->toBe('1234567890');
});

test('it rejects values without exactly ten digits', function (string $value) {
    expect(fn () => new PhoneNumber($value))->toThrow(InvalidArgumentException::class);
})->with([
    'too short' => '123456789',
    'too long' => '11234567890',
    'empty' => '',
]);
