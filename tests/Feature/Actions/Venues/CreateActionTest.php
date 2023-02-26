<?php

use App\Actions\Venues\CreateAction;
use App\Data\VenueData;
use App\Models\Venue;
use App\Repositories\VenueRepository;

test('it creates a venue', function () {
    $data = new VenueData('Exampel Venue Name', '123 Main Street', 'Laraville', 'New York', '12345');

    $this->mock(VenueRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Venue());

    CreateAction::run($data);
});
