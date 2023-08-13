<?php

use App\Data\VenueData;
use App\Models\Venue;
use App\Repositories\VenueRepository;

test('creates an venue', function () {
    $data = new VenueData('Example Venue Name', '123 Main Street', 'Laraville', 'New York', '12345');

    (new VenueRepository())->create($data);

    expect(Venue::latest()->first())
        ->name->toEqual('Example Venue Name')
        ->street_address->toEqual('123 Main Street')
        ->city->toEqual('Laraville')
        ->state->toEqual('New York')
        ->zip->toEqual('12345');
});

test('updates an venue', function () {
    $venue = Venue::factory()->create();
    $data = new VenueData('Example Venue Name', '123 Main Street', 'Laraville', 'New York', '12345');

    (new VenueRepository())->update($venue, $data);

    expect($venue->fresh())
        ->name->toEqual('Example Venue Name')
        ->street_address->toEqual('123 Main Street')
        ->city->toEqual('Laraville')
        ->state->toEqual('New York')
        ->zip->toEqual('12345');
});

test('it can delete an venue', function () {
    $venue = Venue::factory()->create();

    (new VenueRepository())->delete($venue);

    expect($venue->fresh())
        ->deleted_at->not->toBeNull();
});

test('it can restore a trashed venue', function () {
    $venue = Venue::factory()->trashed()->create();

    (new VenueRepository())->restore($venue);

    expect($venue->fresh())
        ->deleted_at->toBeNull();
});
