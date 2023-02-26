<?php

use App\Actions\Venues\RestoreAction;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use function Pest\Laravel\mock;

test('it restores a soft deleted venue', function () {
    $venue = Venue::factory()->trashed()->create();

    mock(VenueRepository::class)
        ->shouldReceive('restore')
        ->once()
        ->with($venue);

    RestoreAction::run($venue);
});
