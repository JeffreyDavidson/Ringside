<?php

declare(strict_types=1);

use App\Actions\Venues\RestoreAction;
use App\Models\Shared\Venue;
use App\Repositories\VenueRepository;

beforeEach(function () {
    $this->venueRepository = $this->mock(VenueRepository::class);
});

test('it restores a soft deleted venue', function () {
    $venue = Venue::factory()->trashed()->create();

    $this->venueRepository
        ->shouldReceive('restore')
        ->once()
        ->with($venue);

    resolve(RestoreAction::class)->handle($venue);
});
