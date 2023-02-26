<?php

use App\Actions\Venues\DeleteAction;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use function Pest\Laravel\mock;

test('it deletes a venue', function () {
    $venue = Venue::factory()->create();

    mock(VenueRepository::class)
        ->shouldReceive('delete')
        ->once()
        ->with($venue);

    DeleteAction::run($venue);
});
