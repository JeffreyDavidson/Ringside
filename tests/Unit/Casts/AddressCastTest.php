<?php

declare(strict_types=1);

use App\Models\Events\Venue;
use App\ValueObjects\Address;

test('it casts venue address columns to an address', function () {
    $venue = Venue::factory()->create([
        'street_address' => '4 Pennsylvania Plaza',
        'city' => 'New York',
        'state' => 'New York',
        'zipcode' => '10001',
    ]);

    expect($venue->address)->toEqual(
        new Address('4 Pennsylvania Plaza', 'New York', 'New York', '10001'),
    );
});

test('it stores an address across the existing venue columns', function () {
    $venue = Venue::factory()->create([
        'address' => new Address('4 Pennsylvania Plaza', 'New York', 'New York', '10001'),
    ]);

    expect($venue->getRawOriginal('street_address'))->toBe('4 Pennsylvania Plaza')
        ->and($venue->getRawOriginal('city'))->toBe('New York')
        ->and($venue->getRawOriginal('state'))->toBe('New York')
        ->and($venue->getRawOriginal('zipcode'))->toBe('10001');
});
