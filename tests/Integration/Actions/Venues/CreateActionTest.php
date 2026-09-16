<?php

declare(strict_types=1);

use App\Actions\Venues\CreateAction;
use App\Data\Events\VenueData;
use App\Models\Events\Venue;

test('it creates a venue with its address data', function (): void {
    $data = new VenueData('Madison Square Garden', '4 Pennsylvania Plaza', 'New York', 'New York', '10001');

    $venue = resolve(CreateAction::class)->handle($data);

    expect($venue)
        ->toBeInstanceOf(Venue::class)
        ->name->toBe('Madison Square Garden')
        ->street_address->toBe('4 Pennsylvania Plaza')
        ->city->toBe('New York')
        ->state->toBe('New York')
        ->zipcode->toBe('10001');
});
