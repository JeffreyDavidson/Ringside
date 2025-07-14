<?php

declare(strict_types=1);

use App\Actions\Venues\UpdateAction;
use App\Data\Shared\VenueData;
use App\Models\Events\Venue;
use App\Repositories\VenueRepository;

beforeEach(function () {
    $this->venueRepository = $this->mock(VenueRepository::class);
});

test('it updates a venue', function () {
    $data = new VenueData('Example Venue Name', '123 Main Street', 'New York City', 'New York', '12345');
    $venue = Venue::factory()->create();

    $this->venueRepository
        ->shouldReceive('update')
        ->once()
        ->with($venue, $data)
        ->andReturns($venue);

    resolve(UpdateAction::class)->handle($venue, $data);
});
