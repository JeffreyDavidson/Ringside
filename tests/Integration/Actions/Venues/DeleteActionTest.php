<?php

declare(strict_types=1);

use App\Actions\Venues\DeleteAction;
use App\Models\Venue;
use App\Repositories\VenueRepository;

beforeEach(function () {
    $this->venueRepository = $this->mock(VenueRepository::class);
});

test('it deletes a venue', function () {
    $venue = Venue::factory()->create();

    $this->venueRepository
        ->shouldReceive('delete')
        ->once()
        ->with($venue);

    app(DeleteAction::class)->handle($venue);
});
