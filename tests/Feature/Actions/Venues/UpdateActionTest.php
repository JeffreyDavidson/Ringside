<?php

use App\Actions\Venues\UpdateAction;
use App\Data\VenueData;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use function Pest\Laravel\mock;

test('it updates a venue', function () {
    $data = new VenueData('Exampel Venue Name', '123 Main Street', 'Laraville', 'New York', '12345');
    $venue = Venue::factory()->create();

    mock(VenueRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($venue, $data)
        ->andReturns($venue);

    UpdateAction::run($venue, $data);
});
