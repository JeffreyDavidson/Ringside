<?php

declare(strict_types=1);

use App\ValueObjects\Address;

test('it represents and formats a venue address', function () {
    $address = new Address('4 Pennsylvania Plaza', 'New York', 'New York', '10001');

    expect($address->formatted())->toBe('4 Pennsylvania Plaza, New York, New York 10001')
        ->and($address->toAttributes())->toBe([
            'street_address' => '4 Pennsylvania Plaza',
            'city' => 'New York',
            'state' => 'New York',
            'zipcode' => '10001',
        ]);
});

test('it rejects incomplete address fields', function (string $street, string $city, string $state) {
    expect(fn () => new Address($street, $city, $state, '10001'))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'missing street' => ['', 'New York', 'New York'],
    'missing city' => ['4 Pennsylvania Plaza', '', 'New York'],
    'missing state' => ['4 Pennsylvania Plaza', 'New York', ''],
]);

test('it rejects invalid ZIP codes', function (string $zipcode) {
    expect(fn () => new Address('4 Pennsylvania Plaza', 'New York', 'New York', $zipcode))
        ->toThrow(InvalidArgumentException::class);
})->with(['1234', '123456', 'abcde']);
