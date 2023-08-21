<?php

use App\Actions\Venues\RestoreAction;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->venueRepository = mock(VenueRepository::class);
});

test('it restores a soft deleted venue', function () {
    $venue = Venue::factory()->trashed()->create();

    $this->venueRepository
        ->shouldReceive('restore')
        ->once()
        ->with($venue);

    RestoreAction::run($venue);
});