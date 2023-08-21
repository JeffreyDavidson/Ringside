<?php

use App\Actions\Venues\DeleteAction;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->venueRepository = mock(VenueRepository::class);
});

test('it deletes a venue', function () {
    $venue = Venue::factory()->create();

    $this->venueRepository
        ->shouldReceive('delete')
        ->once()
        ->with($venue);

    DeleteAction::run($venue);
});