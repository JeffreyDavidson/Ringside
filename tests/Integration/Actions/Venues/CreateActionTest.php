<?php

use App\Actions\Venues\CreateAction;
use App\Data\VenueData;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->venueRepository = mock(VenueRepository::class);
});

test('it creates a venue', function () {
    $data = new VenueData('Example Venue Name', '123 Main Street', 'New York City', 'New York', '12345');

    $this->venueRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Venue());

    CreateAction::run($data);
});
